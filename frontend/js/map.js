// Работа с Яндекс.Картами
let map;
let placemarks = [];

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

async function loadRinksOnMap() {
    const result = await API.getRinks();
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
                    balloonContent: `<b>${rink.name}</b><br>${rink.address || ''}`,
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
    }
}

// Инициализация при загрузке страницы
if (document.getElementById('map')) {
    // Если Yandex Maps не загружен, показываем список катков
    if (typeof ymaps === 'undefined') {
        console.log('Yandex Maps API не загружен, показываем список катков');
        loadRinksForMap();
    } else {
        initMap();
    }
}

// Загрузка катков для отображения списком (если карта не работает)
async function loadRinksForMap() {
    const result = await API.getRinks();
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
