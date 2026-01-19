// Главный файл - инициализация приложения
function logout() {
    if (typeof Auth !== 'undefined') {
        Auth.logout();
    }
}

// Поиск
function searchRinks() {
    const query = document.getElementById('searchInput').value;
    loadRinks({ search: query });
}

// Применение фильтров
function applyFilters() {
    const filters = {
        district: document.getElementById('districtFilter')?.value || '',
        is_paid: document.getElementById('paidFilter')?.value || '',
        equipment: document.getElementById('equipmentFilter')?.value || ''
    };
    
    loadRinks(filters);
}

// Живой поиск
if (document.getElementById('searchInput')) {
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (e.target.value.length >= 2) {
                searchRinks();
            } else if (e.target.value.length === 0) {
                loadRinks();
            }
        }, 500);
    });
}

// Инициализация при загрузке
window.addEventListener('load', () => {
    // Обновляем навигацию
    if (typeof Auth !== 'undefined') {
        Auth.updateNavbar();
    }
    
    // Загружаем катки на главной странице (если функции доступны)
    setTimeout(() => {
        if (document.getElementById('rinksList')) {
            if (typeof loadRinks === 'function') {
                loadRinks();
            }
            if (typeof loadDistricts === 'function') {
                setTimeout(() => loadDistricts(), 100);
            }
        }
        
        // Загружаем отзывы на странице катка
        if (document.getElementById('reviewsList')) {
            if (typeof loadReviews === 'function') {
                loadReviews();
            }
        }
    }, 100);
});
