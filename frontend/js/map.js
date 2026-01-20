// Работа с Яндекс.Картами
let map;
let placemarks = [];
let currentFilters = {};

async function initMap() {
    if (typeof ymaps === 'undefined') {
        console.error('Yandex Maps API не загружен');
        return;
    }
    
    ymaps.ready(() => {
        map = new ymaps.Map('map', {
            center: [55.751244, 37.618423], // Москва
            zoom: 10
        });
        
        loadRinksOnMap();
    });
}

async function loadRinksOnMap(filters = {}) {
    const result = await API.getRinks(filters);
    if (!result.success || !map) return;
    
    // Удаляем старые метки
    placemarks.forEach(pm => map.geoObjects.remove(pm));
    placemarks = [];
    
    const rinks = result.data.results || [];
    rinks.forEach(rink => {
        if (rink.latitude && rink.longitude) {
            const placemark = new ymaps.Placemark(
                [rink.latitude, rink.longitude],
                {
                    balloonContent: `<b>${rink.name}</b><br>${rink.address || ''}<br><a href="rink.html?id=${rink.id}">Подробнее</a>`,
                    hintContent: rink.name
                },
                {
                    preset: rink.is_paid ? 'islands#blueIcon' : 'islands#greenIcon'
                }
            );
            
            placemark.events.add('click', () => {
                window.location.href = `rink.html?id=${rink.id}`;
            });
            
            map.geoObjects.add(placemark);
            placemarks.push(placemark);
        }
    });
    
    if (placemarks.length > 0) {
        map.setBounds(map.geoObjects.getBounds());
    } else {
        // Если нет катков, показываем сообщение
        map.setCenter([55.751244, 37.618423], 10);
    }
}

// Применить фильтры
let filterTimeout;
function applyFilters() {
    // Используем debounce (задержку), чтобы не спамить API при вводе текста
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(async () => {
        const filters = {};
        
        // Поиск
        const search = document.getElementById('searchInput')?.value.trim();
        if (search) filters.search = search;
        
        // Район
        const district = document.getElementById('districtFilter')?.value;
        if (district) filters.district = district;
        
        // Тип
        const paid = document.getElementById('paidFilter')?.value;
        if (paid !== '') filters.is_paid = paid === '1';
        
        // Чекбоксы оборудования
        if (document.getElementById('filter_rental')?.checked) filters.has_equipment_rental = true;
        if (document.getElementById('filter_locker')?.checked) filters.has_locker_room = true;
        if (document.getElementById('filter_cafe')?.checked) filters.has_cafe = true;
        if (document.getElementById('filter_wifi')?.checked) filters.has_wifi = true;
        if (document.getElementById('filter_atm')?.checked) filters.has_atm = true;
        if (document.getElementById('filter_medpoint')?.checked) filters.has_medpoint = true;
        if (document.getElementById('filter_disabled')?.checked) filters.is_disabled_accessible = true;
        
        currentFilters = filters;
        
        // Обновляем карту и список моментально
        await loadRinksOnMap(filters);
        await loadRinksForMap(filters);
    }, 300); // Задержка 300мс
}

// Сбросить фильтры
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('districtFilter').value = '';
    document.getElementById('paidFilter').value = '';
    
    // Сбрасываем все чекбоксы
    const checkboxes = ['filter_rental', 'filter_locker', 'filter_cafe', 'filter_wifi', 'filter_atm', 'filter_medpoint', 'filter_disabled'];
    checkboxes.forEach(id => {
        const cb = document.getElementById(id);
        if (cb) cb.checked = false;
    });
    
    applyFilters();
}

// Инициализация при загрузке страницы
if (document.getElementById('map')) {
    // Загружаем список районов
    loadDistrictsForMap();
    
    // Если Yandex Maps не загружен, показываем список катков
    if (typeof ymaps === 'undefined') {
        console.log('Yandex Maps API не загружен, показываем список катков');
        loadRinksForMap();
    } else {
        initMap();
    }
}

// Загрузка катков для отображения списком (если карта не работает)
async function loadRinksForMap(filters = {}) {
    const result = await API.getRinks(filters);
    if (result.success && result.data.results) {
        const container = document.getElementById('rinksListMap');
        if (container) {
            let html = '';
            result.data.results.forEach(rink => {
                const badge = rink.is_paid ? '<span class="badge bg-warning">Платный</span>' : '<span class="badge bg-success">Бесплатный</span>';
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">${rink.name}</h5>
                                <p class="card-text text-muted">${rink.address || ''}</p>
                                <p>${badge} ${rink.district ? '<span class="badge bg-secondary">' + rink.district + '</span>' : ''}</p>
                                <a href="rink.html?id=${rink.id}" class="btn btn-primary btn-sm">Подробнее</a>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html || '<div class="col-12"><p class="text-muted">Катки не найдены</p></div>';
        }
    }
}

// Загрузка списка районов для фильтра
async function loadDistrictsForMap() {
    const result = await API.getRinks();
    if (result.success) {
        const districts = [...new Set(result.data.results.map(r => r.district).filter(Boolean))].sort();
        const select = document.getElementById('districtFilter');
        if (select) {
            districts.forEach(district => {
                const option = document.createElement('option');
                option.value = district;
                option.textContent = district;
                select.appendChild(option);
            });
        }
    }
}
