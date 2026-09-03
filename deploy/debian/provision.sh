#!/usr/bin/env bash
#
# TruckMarket - Debian 12 (bookworm) / 13 (trixie) production provisioning.
#
# Installs the latest stable releases from the vendors' own repositories:
#   nginx (nginx.org stable), MySQL 8.4 LTS (repo.mysql.com), PHP-FPM (deb.sury.org),
#   Redis (packages.redis.io), Node.js LTS (NodeSource), Composer (getcomposer.org),
# then configures the php-fpm pool and php.ini, the nginx vhost, the MySQL database and user,
# Redis, the queue worker (systemd), the scheduler (cron), the firewall (ufw), fail2ban,
# automatic security updates, and deploys the application itself.
#
# Usage, as root on a fresh server (all variables are optional):
#   DOMAIN=truckmarket.example.com LE_EMAIL=admin@example.com bash provision.sh
#
# Re-running is safe: generated secrets live in /root/truckmarket-credentials and are reused.
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/truckmarket}"
APP_USER="${APP_USER:-deploy}"                 # owns the code, runs php-fpm and the queue worker
DOMAIN="${DOMAIN:-}"                           # empty = serve on the server IP over plain HTTP
LE_EMAIL="${LE_EMAIL:-}"                       # Let's Encrypt contact; HTTPS is set up only when DOMAIN and LE_EMAIL are given
REPO_URL="${REPO_URL:-https://github.com/zixela/truckmarket.git}"   # private repo: use git@github.com:zixela/truckmarket.git + a deploy key
REPO_BRANCH="${REPO_BRANCH:-main}"
PHP_VERSION="${PHP_VERSION:-8.4}"              # the app needs 8.3+; set 8.5 once its dependencies are verified on it
NODE_MAJOR="${NODE_MAJOR:-24}"
MYSQL_CHANNEL="${MYSQL_CHANNEL:-mysql-8.4-lts}"
DB_NAME="${DB_NAME:-truckmarket}"
DB_USER="${DB_USER:-truckmarket}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@truckmarket.test}"   # admin panel login; password is generated
CREDENTIALS_FILE=/root/truckmarket-credentials

export DEBIAN_FRONTEND=noninteractive
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33mWARNING: %s\033[0m\n' "$*" >&2; }
die()  { printf '\033[1;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "run this script as root"
. /etc/os-release
CODENAME="${VERSION_CODENAME:-}"
[[ "${ID:-}" == "debian" && "$CODENAME" =~ ^(bookworm|trixie)$ ]] \
    || die "Debian 12 (bookworm) or 13 (trixie) is required, found: ${PRETTY_NAME:-unknown}"

# secret KEY -> prints the stored value, generating it on first use.
secret() {
    local key="$1"
    touch "$CREDENTIALS_FILE"
    chmod 600 "$CREDENTIALS_FILE"
    if ! grep -q "^${key}=" "$CREDENTIALS_FILE"; then
        printf '%s=%s\n' "$key" "$(openssl rand -base64 36 | tr -dc 'A-Za-z0-9' | cut -c1-32)" >> "$CREDENTIALS_FILE"
    fi
    sed -n "s/^${key}=//p" "$CREDENTIALS_FILE"
}

# run_app CMD... -> runs a command inside the app directory as the app user.
run_app() { sudo -u "$APP_USER" -H env -C "$APP_DIR" "$@"; }

# ------------------------------------------------------------------ base system
log "Base packages"
apt-get update
apt-get -y full-upgrade
apt-get -y install ca-certificates curl gnupg lsb-release sudo git unzip zip acl openssl cron logrotate \
    ufw fail2ban unattended-upgrades \
    jpegoptim optipng pngquant gifsicle webp libavif-bin   # image optimizers used by spatie/medialibrary
install -d -m 0755 /etc/apt/keyrings

if [[ "$(free -m | awk '/^Mem:/{print $2}')" -lt 2048 ]] && ! swapon --show | grep -q .; then
    log "Adding a 2 GB swap file (small server, composer needs the headroom)"
    fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# ------------------------------------------------------------------ repositories
log "nginx repository (nginx.org, stable branch)"
curl -fsSL https://nginx.org/keys/nginx_signing.key | gpg --dearmor --yes -o /etc/apt/keyrings/nginx.gpg
echo "deb [signed-by=/etc/apt/keyrings/nginx.gpg] http://nginx.org/packages/debian ${CODENAME} nginx" \
    > /etc/apt/sources.list.d/nginx.list
printf 'Package: *\nPin: origin nginx.org\nPin: release o=nginx\nPin-Priority: 900\n' > /etc/apt/preferences.d/99nginx

log "PHP ${PHP_VERSION} repository (deb.sury.org)"
curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/sury-php.gpg
echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${CODENAME} main" \
    > /etc/apt/sources.list.d/sury-php.list

log "MySQL repository (repo.mysql.com, ${MYSQL_CHANNEL})"
curl -fsSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2023 | gpg --dearmor --yes -o /etc/apt/keyrings/mysql.gpg
mysql_codename="$CODENAME"
if ! curl -fsSI "https://repo.mysql.com/apt/debian/dists/${CODENAME}/Release" >/dev/null; then
    warn "repo.mysql.com has no ${CODENAME} packages yet, using the bookworm build"
    mysql_codename=bookworm
fi
echo "deb [signed-by=/etc/apt/keyrings/mysql.gpg] https://repo.mysql.com/apt/debian/ ${mysql_codename} ${MYSQL_CHANNEL}" \
    > /etc/apt/sources.list.d/mysql.list
echo "mysql-community-server mysql-community-server/root-pass password " | debconf-set-selections
echo "mysql-community-server mysql-community-server/re-root-pass password " | debconf-set-selections

log "Redis repository (packages.redis.io)"
if curl -fsSI "https://packages.redis.io/deb/dists/${CODENAME}/Release" >/dev/null; then
    curl -fsSL https://packages.redis.io/gpg | gpg --dearmor --yes -o /etc/apt/keyrings/redis.gpg
    echo "deb [signed-by=/etc/apt/keyrings/redis.gpg] https://packages.redis.io/deb ${CODENAME} main" \
        > /etc/apt/sources.list.d/redis.list
else
    warn "packages.redis.io has no ${CODENAME} packages yet, falling back to the Debian package"
    rm -f /etc/apt/sources.list.d/redis.list
fi

log "Node.js ${NODE_MAJOR}.x repository (NodeSource)"
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor --yes -o /etc/apt/keyrings/nodesource.gpg
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list

apt-get update

# ------------------------------------------------------------------ packages
log "Installing nginx, MySQL, PHP ${PHP_VERSION}-FPM, Redis, Node.js"
redis_pkg=redis-server
if ! apt-cache show redis-server >/dev/null 2>&1; then
    redis_pkg=valkey-server   # Redis-compatible fork shipped by Debian 13
    warn "no redis-server package available, installing ${redis_pkg}"
fi
php_pkgs=()
for ext in cli fpm opcache mysql redis gd intl mbstring xml curl zip bcmath readline sqlite3; do
    php_pkgs+=("php${PHP_VERSION}-${ext}")
done
apt-get -y install nginx mysql-server "${php_pkgs[@]}" "$redis_pkg" nodejs

log "Composer"
expected_sig="$(curl -fsSL https://composer.github.io/installer.sig)"
curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
[[ "$(sha384sum /tmp/composer-setup.php | cut -d' ' -f1)" == "$expected_sig" ]] || die "composer installer checksum mismatch"
php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
rm -f /tmp/composer-setup.php

# ------------------------------------------------------------------ app user
log "Application user ${APP_USER} and ${APP_DIR}"
id -u "$APP_USER" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$APP_USER"
install -d -m 0755 "$(dirname "$APP_DIR")"
install -d -o "$APP_USER" -g "$APP_USER" -m 0755 "$APP_DIR"

# ------------------------------------------------------------------ PHP
log "PHP-FPM pool and php.ini overrides"
install -m 0644 "$SCRIPT_DIR/php/99-truckmarket.ini" "/etc/php/${PHP_VERSION}/fpm/conf.d/99-truckmarket.ini"
install -m 0644 "$SCRIPT_DIR/php/99-truckmarket-cli.ini" "/etc/php/${PHP_VERSION}/cli/conf.d/99-truckmarket-cli.ini"
sed -e "s|__PHP_VERSION__|${PHP_VERSION}|g" -e "s|__APP_USER__|${APP_USER}|g" \
    "$SCRIPT_DIR/php/truckmarket-pool.conf" > "/etc/php/${PHP_VERSION}/fpm/pool.d/truckmarket.conf"
if [[ -f "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" ]]; then
    mv "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.disabled"
fi

# ------------------------------------------------------------------ nginx
log "nginx vhost"
sed -i -E 's/^user\s+.*/user  www-data;/' /etc/nginx/nginx.conf
rm -f /etc/nginx/conf.d/default.conf
sed -e "s|__SERVER_NAME__|${DOMAIN:-_}|g" -e "s|__APP_DIR__|${APP_DIR}|g" -e "s|__PHP_VERSION__|${PHP_VERSION}|g" \
    "$SCRIPT_DIR/nginx/truckmarket.conf" > /etc/nginx/conf.d/truckmarket.conf

# Behind the Cloudflare proxy the app must see the visitor's IP (login throttling, view counter),
# not Cloudflare's. Only requests from Cloudflare's published ranges are rewritten, so this is
# harmless when the site is not proxied.
if cf_ranges="$(curl -fsSL https://www.cloudflare.com/ips-v4 https://www.cloudflare.com/ips-v6)"; then
    {
        echo "# Cloudflare edge ranges (fetched $(date -u +%F)); refresh by re-running provision.sh"
        for range in $cf_ranges; do echo "set_real_ip_from ${range};"; done
        echo "real_ip_header CF-Connecting-IP;"
    } > /etc/nginx/conf.d/00-cloudflare-real-ip.conf
else
    warn "could not fetch Cloudflare IP ranges; visitor IPs will be Cloudflare's if the site is proxied"
fi

# ------------------------------------------------------------------ MySQL
log "MySQL configuration, database ${DB_NAME} and user ${DB_USER}"
install -m 0644 "$SCRIPT_DIR/mysql/zz-truckmarket.cnf" /etc/mysql/mysql.conf.d/zz-truckmarket.cnf
systemctl enable --now mysql
systemctl restart mysql
DB_PASSWORD="$(secret DB_PASSWORD)"
mysql --protocol=socket -uroot <<SQL || die "cannot reach MySQL as root over the socket - see /var/log/mysql/error.log"
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ------------------------------------------------------------------ Redis
log "Redis (password, memory limit, persistence)"
REDIS_PASSWORD="$(secret REDIS_PASSWORD)"
if [[ -f /etc/redis/redis.conf ]]; then
    redis_conf=/etc/redis/redis.conf; redis_svc=redis-server
else
    redis_conf=/etc/valkey/valkey.conf; redis_svc=valkey-server
fi
redis_set() {   # redis_set DIRECTIVE VALUE -> replaces the (possibly commented) directive or appends it
    if grep -qE "^#? ?$1 " "$redis_conf"; then
        sed -i -E "s|^#? ?$1 .*|$1 $2|" "$redis_conf"
    else
        printf '%s %s\n' "$1" "$2" >> "$redis_conf"
    fi
}
redis_set bind "127.0.0.1 -::1"
redis_set requirepass "$REDIS_PASSWORD"
redis_set maxmemory 256mb
redis_set maxmemory-policy volatile-lru      # only keys with a TTL (cache, sessions) may be evicted; queue jobs never are
redis_set appendonly yes                     # queued jobs survive a restart
systemctl enable --now "$redis_svc"
systemctl restart "$redis_svc"

# ------------------------------------------------------------------ application
if [[ ! -e "$APP_DIR/artisan" ]]; then
    log "Cloning ${REPO_URL} (${REPO_BRANCH})"
    run_app git clone --branch "$REPO_BRANCH" "$REPO_URL" "$APP_DIR"
fi

log "Environment file"
ENV_FILE="$APP_DIR/.env"
[[ -f "$ENV_FILE" ]] || run_app cp .env.example .env
set_env() {   # set_env KEY VALUE -> replaces the key or appends it
    if grep -qE "^$1=" "$ENV_FILE"; then
        sed -i -E "s|^$1=.*|$1=$2|" "$ENV_FILE"
    else
        printf '%s=%s\n' "$1" "$2" >> "$ENV_FILE"
    fi
}
if [[ -n "$DOMAIN" && -n "$LE_EMAIL" ]]; then
    app_url="https://${DOMAIN}"
    set_env SESSION_SECURE_COOKIE true
else
    app_url="http://${DOMAIN:-$(hostname -I | awk '{print $1}')}"
fi
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "$app_url"
set_env LOG_CHANNEL daily
set_env LOG_LEVEL warning
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASSWORD"
set_env REDIS_CLIENT phpredis
set_env REDIS_PASSWORD "$REDIS_PASSWORD"
set_env MAIL_FROM_ADDRESS "\"noreply@${DOMAIN:-truckmarket.test}\""
chown "$APP_USER:$APP_USER" "$ENV_FILE"
chmod 640 "$ENV_FILE"
grep -qE '^APP_KEY=.+' "$ENV_FILE" || run_app php artisan key:generate --force

log "Composer and npm dependencies, asset build"
run_app composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
run_app npm ci --no-audit --no-fund
run_app npm run build

log "Database migration, seed and admin account"
run_app php artisan migrate --force
run_app php artisan db:seed --force          # roles, ZIP codes, admin user (demo data only in APP_ENV=local)
run_app php artisan storage:link --force
ADMIN_PASSWORD="$(secret ADMIN_PASSWORD)"
run_app php artisan tinker --execute="\$u = App\Models\User::firstWhere('email', '${ADMIN_EMAIL}') ?? App\Models\User::firstWhere('email', 'admin@truckmarket.test'); \$u->forceFill(['email' => '${ADMIN_EMAIL}', 'password' => bcrypt('${ADMIN_PASSWORD}')])->save();"

log "Laravel caches"
run_app php artisan optimize
run_app php artisan filament:optimize
run_app php artisan translations:sync

# ------------------------------------------------------------------ services
log "Queue worker (systemd) and scheduler (cron)"
sed -e "s|__APP_USER__|${APP_USER}|g" -e "s|__APP_DIR__|${APP_DIR}|g" \
    "$SCRIPT_DIR/systemd/truckmarket-queue.service" > /etc/systemd/system/truckmarket-queue.service
sed -e "s|__APP_USER__|${APP_USER}|g" -e "s|__APP_DIR__|${APP_DIR}|g" \
    "$SCRIPT_DIR/cron/truckmarket" > /etc/cron.d/truckmarket
chmod 0644 /etc/cron.d/truckmarket
systemctl daemon-reload
systemctl enable --now "php${PHP_VERSION}-fpm" nginx truckmarket-queue
systemctl restart "php${PHP_VERSION}-fpm"
nginx -t
systemctl reload nginx
systemctl restart truckmarket-queue

# ------------------------------------------------------------------ hardening
log "Firewall, fail2ban, unattended upgrades"
ssh_port="$(sshd -T 2>/dev/null | awk '/^port /{print $2; exit}')"
ufw allow "${ssh_port:-22}/tcp"
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
systemctl enable --now fail2ban
dpkg-reconfigure -f noninteractive unattended-upgrades

if [[ -n "$DOMAIN" && -n "$LE_EMAIL" ]]; then
    log "HTTPS certificate for ${DOMAIN} (Let's Encrypt)"
    apt-get -y install certbot python3-certbot-nginx
    certbot --nginx --non-interactive --agree-tos --redirect -m "$LE_EMAIL" -d "$DOMAIN" \
        || warn "certbot failed. Point ${DOMAIN} at this server, then run: certbot --nginx --redirect -d ${DOMAIN}"
fi

# ------------------------------------------------------------------ done
log "Done"
cat <<EOF

  Site:         ${app_url}
  Admin panel:  ${app_url}/admin   (${ADMIN_EMAIL} / see ADMIN_PASSWORD in ${CREDENTIALS_FILE})
  Credentials:  ${CREDENTIALS_FILE}   (DB, Redis and admin passwords - keep it safe)
  App dir:      ${APP_DIR}   (.env, logs in storage/logs)
  Versions:     $(nginx -v 2>&1 | sed 's/.*nginx\///') | mysql $(mysqld --version | awk '{print $3}') | php $(php -r 'echo PHP_VERSION;') | node $(node -v)

  Next steps:
   - set real mail credentials in .env (MAIL_*), then:
       sudo -u ${APP_USER} php ${APP_DIR}/artisan optimize && systemctl reload php${PHP_VERSION}-fpm
   - optional API keys in .env: STRIPE_SECRET, FMCSA_WEBKEY, TWILIO_*, GOOGLE_CLIENT_*
   - releases: bash ${SCRIPT_DIR}/deploy.sh
   - queue worker logs: journalctl -u truckmarket-queue -f
EOF
