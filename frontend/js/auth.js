// Работа с авторизацией
const Auth = {
    isLoggedIn() {
        return localStorage.getItem('user') !== null;
    },
    
    getUser() {
        const userStr = localStorage.getItem('user');
        return userStr ? JSON.parse(userStr) : null;
    },
    
    async logout() {
        if (typeof API !== 'undefined') {
            await API.logout();
        }
        window.location.href = 'index.html';
    },
    
    updateNavbar() {
        const user = this.getUser();
        const authLinks = document.getElementById('authLinks');
        const userMenu = document.getElementById('userMenu');
        const logoutBtn = document.getElementById('logoutBtn');
        const userName = document.getElementById('userName');
        
        if (user) {
            if (authLinks) authLinks.classList.add('d-none');
            if (userMenu) userMenu.classList.remove('d-none');
            if (logoutBtn) logoutBtn.classList.remove('d-none');
            if (userName) userName.textContent = user.name;
        } else {
            if (authLinks) authLinks.classList.remove('d-none');
            if (userMenu) userMenu.classList.add('d-none');
            if (logoutBtn) logoutBtn.classList.add('d-none');
        }
    }
};

// Обновляем навигацию при загрузке
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Auth.updateNavbar());
} else {
    Auth.updateNavbar();
}
