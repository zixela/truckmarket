import Alpine from 'alpinejs';

// Theme preference lives in localStorage ('dark' | 'light'); the inline script in
// layouts/app.blade.php applies it to <html> before first paint, this store keeps it in sync.
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = ! this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
});

window.Alpine = Alpine;
Alpine.start();
