// Mesa Digital - Frontend JS (Alpine.js integration & utilities)

document.addEventListener('DOMContentLoaded', () => {
    // Por defecto modo claro; solo aplica oscuro si el usuario lo eligió explícitamente
    const savedTheme = localStorage.getItem('theme') || (document.cookie.match(/theme=([^;]+)/) || [])[1];
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});

// Theme switcher function
window.toggleTheme = function() {
    const isDark = document.documentElement.classList.toggle('dark');
    const theme = isDark ? 'dark' : 'light';
    localStorage.setItem('theme', theme);
    document.cookie = `theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;
};

// Toast notification helper
window.showToast = function(message, type = 'info') {
    window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
};
