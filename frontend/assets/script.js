document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Retrieve saved theme preference
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        body.classList.add('dark-theme');
        if (themeToggle) themeToggle.textContent = '☀️ Light Mode';
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-theme');
            if (body.classList.contains('dark-theme')) {
                localStorage.setItem('theme', 'dark');
                themeToggle.textContent = '☀️ Light Mode';
            } else {
                localStorage.setItem('theme', 'light');
                themeToggle.textContent = '🌙 Dark Mode';
            }
        });
    }
});

// Toast Notification Handler
function trackDownload(appName) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = `Downloading ${appName}...`;
    toast.classList.add('show');
    
    setTimeout(() => { 
        toast.classList.remove('show'); 
    }, 3500);
}