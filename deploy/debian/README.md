# TruckMarket on Debian — nginx + MySQL 8.4 + PHP-FPM + Redis

Target: a fresh **Debian 12 (bookworm) or 13 (trixie)** server, root access, 2 GB RAM or more.
Everything installs the **latest stable** releases from the vendors' own repositories:

| Component | Source | Version |
|---|---|---|
| nginx | nginx.org (stable branch) | latest stable (1.28+) |
| MySQL | repo.mysql.com | 8.4 LTS |
| PHP-FPM + extensions | deb.sury.org | 8.4 (`PHP_VERSION=8.5` once verified) |
| Redis | packages.redis.io | latest stable (falls back to Debian's package) |
| Node.js | NodeSource | 24 LTS (asset build only) |
| Composer | getcomposer.org | latest |

PHP extensions installed: cli, fpm, opcache, mysql, redis, gd, intl, mbstring, xml, curl, zip, bcmath, readline, sqlite3.
System tools: git, unzip, acl, ufw, fail2ban, unattended-upgrades, certbot, image optimizers (jpegoptim, optipng, pngquant, gifsicle, webp, avif).

## 1. Provision the server (one command)

```bash
apt-get update && apt-get -y install git
git clone https://github.com/zixela/truckmarket.git /tmp/truckmarket-src
cd /tmp/truckmarket-src/deploy/debian
DOMAIN=truckmarket.example.com LE_EMAIL=admin@example.com bash provision.sh
```

Point the domain's DNS `A` record at the server first; with `DOMAIN` + `LE_EMAIL` the script also issues the
Let's Encrypt certificate and forces HTTPS. Without them the site is served over HTTP on the server IP.
`LE_EMAIL` must be a real mailbox (Let's Encrypt rejects `example.com` addresses).

**Domain behind Cloudflare (orange-cloud proxy):**

1. In Cloudflare DNS set the `A` record (and `www` if used) to the new server's IP; the proxy may stay on.
2. During provisioning set SSL/TLS mode to **Full** and keep **Always Use HTTPS** off, so the HTTP-01
   challenge reaches nginx. After the run switch to **Full (strict)** and turn Always Use HTTPS on.
3. Visitor IPs: the script writes `/etc/nginx/conf.d/00-cloudflare-real-ip.conf` (Cloudflare ranges +
   `CF-Connecting-IP`), so PHP sees the real client address. Re-run `provision.sh` occasionally to refresh the ranges.

Private repository: create a deploy key for the app user and use the SSH URL.

```bash
adduser --disabled-password --gecos "" deploy
sudo -u deploy ssh-keygen -t ed25519 -N "" -f /home/deploy/.ssh/id_ed25519
cat /home/deploy/.ssh/id_ed25519.pub        # add as a read-only deploy key on GitHub
REPO_URL=git@github.com:zixela/truckmarket.git DOMAIN=... LE_EMAIL=... bash provision.sh
```

Variables (all optional): `DOMAIN`, `LE_EMAIL`, `REPO_URL`, `REPO_BRANCH` (main), `PHP_VERSION` (8.4),
`NODE_MAJOR` (24), `MYSQL_CHANNEL` (mysql-8.4-lts), `APP_DIR` (/var/www/truckmarket), `APP_USER` (deploy),
`DB_NAME`, `DB_USER` (truckmarket), `ADMIN_EMAIL` (admin@truckmarket.test).

The script is safe to re-run. Generated passwords (MySQL, Redis, admin login) are kept in
`/root/truckmarket-credentials` (mode 600) and reused.

When it finishes:

- site: `https://DOMAIN`, admin panel: `https://DOMAIN/admin` (login `ADMIN_EMAIL`, password from the credentials file)
- fill in real mail credentials in `/var/www/truckmarket/.env` (`MAIL_*`), plus optional `STRIPE_SECRET`,
  `FMCSA_WEBKEY`, `TWILIO_*`, `GOOGLE_CLIENT_*`, then apply them:

```bash
sudo -u deploy php /var/www/truckmarket/artisan optimize && systemctl reload php8.4-fpm
```

## 2. Release a new version

```bash
bash /var/www/truckmarket/deploy/debian/deploy.sh          # BRANCH=main by default
```

Maintenance mode on, `git pull`, composer/npm install, asset build, migrations, cache rebuild,
translations sync, php-fpm reload (opcache does not re-check files in production), queue restart, maintenance off.

## 3. What gets configured

| Piece | File on the server |
|---|---|
| nginx vhost | `/etc/nginx/conf.d/truckmarket.conf` (nginx runs as `www-data`; default vhost removed) |
| php-fpm pool | `/etc/php/8.4/fpm/pool.d/truckmarket.conf` (runs as `deploy`, socket `/run/php/php8.4-fpm-truckmarket.sock`) |
| php.ini | `/etc/php/8.4/fpm/conf.d/99-truckmarket.ini`, `/etc/php/8.4/cli/conf.d/99-truckmarket-cli.ini` |
| MySQL | `/etc/mysql/mysql.conf.d/zz-truckmarket.cnf`; database + user `truckmarket`; root uses socket auth (`mysql -uroot`) |
| Redis | `/etc/redis/redis.conf`: password, `maxmemory 256mb`, `volatile-lru`, AOF on |
| queue worker | `systemctl status truckmarket-queue`, logs: `journalctl -u truckmarket-queue -f` |
| scheduler | `/etc/cron.d/truckmarket` |
| firewall | ufw: SSH port, 80, 443 |
| app | `/var/www/truckmarket` owned by `deploy`; `.env` mode 640; logs in `storage/logs/laravel-*.log` |

## 4. Manual equivalent (the same steps by hand)

```bash
# repositories
install -d /etc/apt/keyrings
curl -fsSL https://nginx.org/keys/nginx_signing.key | gpg --dearmor -o /etc/apt/keyrings/nginx.gpg
echo "deb [signed-by=/etc/apt/keyrings/nginx.gpg] http://nginx.org/packages/debian $(. /etc/os-release; echo $VERSION_CODENAME) nginx" > /etc/apt/sources.list.d/nginx.list
curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/sury-php.gpg
echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(. /etc/os-release; echo $VERSION_CODENAME) main" > /etc/apt/sources.list.d/sury-php.list
curl -fsSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2023 | gpg --dearmor -o /etc/apt/keyrings/mysql.gpg
echo "deb [signed-by=/etc/apt/keyrings/mysql.gpg] https://repo.mysql.com/apt/debian/ bookworm mysql-8.4-lts" > /etc/apt/sources.list.d/mysql.list
curl -fsSL https://packages.redis.io/gpg | gpg --dearmor -o /etc/apt/keyrings/redis.gpg
echo "deb [signed-by=/etc/apt/keyrings/redis.gpg] https://packages.redis.io/deb $(. /etc/os-release; echo $VERSION_CODENAME) main" > /etc/apt/sources.list.d/redis.list
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_24.x nodistro main" > /etc/apt/sources.list.d/nodesource.list
apt-get update

# packages
apt-get -y install nginx mysql-server redis-server nodejs git unzip acl ufw fail2ban certbot python3-certbot-nginx \
  php8.4-cli php8.4-fpm php8.4-opcache php8.4-mysql php8.4-redis php8.4-gd php8.4-intl php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-readline php8.4-sqlite3 \
  jpegoptim optipng pngquant gifsicle webp libavif-bin
curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# configs (templates in this folder; replace __PLACEHOLDERS__)
cp php/99-truckmarket.ini /etc/php/8.4/fpm/conf.d/ && cp php/99-truckmarket-cli.ini /etc/php/8.4/cli/conf.d/
sed 's/__PHP_VERSION__/8.4/g; s/__APP_USER__/deploy/g' php/truckmarket-pool.conf > /etc/php/8.4/fpm/pool.d/truckmarket.conf
mv /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/www.conf.disabled
sed -i 's/^user .*/user  www-data;/' /etc/nginx/nginx.conf && rm -f /etc/nginx/conf.d/default.conf
sed 's/__SERVER_NAME__/truckmarket.example.com/; s|__APP_DIR__|/var/www/truckmarket|g; s/__PHP_VERSION__/8.4/g' nginx/truckmarket.conf > /etc/nginx/conf.d/truckmarket.conf
cp mysql/zz-truckmarket.cnf /etc/mysql/mysql.conf.d/ && systemctl restart mysql
mysql -uroot -e "CREATE DATABASE truckmarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER 'truckmarket'@'localhost' IDENTIFIED BY 'CHANGE_ME'; GRANT ALL ON truckmarket.* TO 'truckmarket'@'localhost';"
sed -i 's/^# requirepass .*/requirepass CHANGE_ME/; s/^# maxmemory <bytes>/maxmemory 256mb/; s/^# maxmemory-policy .*/maxmemory-policy volatile-lru/; s/^appendonly no/appendonly yes/' /etc/redis/redis.conf
systemctl restart redis-server

# application
adduser --disabled-password --gecos "" deploy
sudo -u deploy git clone https://github.com/zixela/truckmarket.git /var/www/truckmarket
cd /var/www/truckmarket && sudo -u deploy cp .env.example .env
# edit .env: APP_ENV=production APP_DEBUG=false APP_URL=https://... LOG_CHANNEL=daily DB_* REDIS_CLIENT=phpredis REDIS_PASSWORD MAIL_*
sudo -u deploy composer install --no-dev --optimize-autoloader
sudo -u deploy npm ci && sudo -u deploy npm run build
sudo -u deploy php artisan key:generate --force
sudo -u deploy php artisan migrate --force && sudo -u deploy php artisan db:seed --force
sudo -u deploy php artisan storage:link
sudo -u deploy php artisan optimize && sudo -u deploy php artisan filament:optimize

# services
sed 's/__APP_USER__/deploy/g; s|__APP_DIR__|/var/www/truckmarket|g' systemd/truckmarket-queue.service > /etc/systemd/system/truckmarket-queue.service
sed 's/__APP_USER__/deploy/g; s|__APP_DIR__|/var/www/truckmarket|g' cron/truckmarket > /etc/cron.d/truckmarket
systemctl daemon-reload && systemctl enable --now php8.4-fpm nginx truckmarket-queue && systemctl restart php8.4-fpm && nginx -t && systemctl reload nginx
ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw --force enable
certbot --nginx --redirect -d truckmarket.example.com
```

The seeded admin is `admin@truckmarket.test` / `password` — change it right away:

```bash
sudo -u deploy php artisan tinker --execute="App\Models\User::firstWhere('email','admin@truckmarket.test')->forceFill(['password' => bcrypt('NEW_PASSWORD')])->save();"
```

## 5. Day-to-day

```bash
systemctl status nginx php8.4-fpm mysql redis-server truckmarket-queue
tail -f /var/www/truckmarket/storage/logs/laravel-$(date +%F).log
tail -f /var/log/nginx/truckmarket.error.log
journalctl -u truckmarket-queue -f
redis-cli -a "$(sed -n 's/^REDIS_PASSWORD=//p' /root/truckmarket-credentials)" ping
mysql -uroot truckmarket
certbot renew --dry-run
```

Any `.env` change: `sudo -u deploy php artisan optimize && systemctl reload php8.4-fpm`.
Switching PHP version later: `PHP_VERSION=8.5 bash provision.sh` (installs the new version and re-points the pool, vhost and services).
