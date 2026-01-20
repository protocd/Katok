// Работа с Яндекс.Картами
let map;
let clusterer;

async function initMap() {
    if (typeof ymaps === 'undefined') {
        return;
    }
    
    ymaps.ready(() => {
        const mapElement = document.getElementById('map');
        if (!mapElement) return;

        map = new ymaps.Map('map', {
            center: [55.751244, 37.618423],
            zoom: 10,
            controls: ['zoomControl', 'typeSelector', 'fullscreenControl', 'geolocationControl']
        });
        
        clusterer = new ymaps.Clusterer({
            clusterIconLayout: 'default#pieChart',
            clusterIconPieChartRadius: 25,
            clusterIconPieChartCoreRadius: 15,
            clusterIconPieChartStrokeWidth: 3,
            
            // Альтернативный вариант - простые кружки с числами:
            // preset: 'islands#invertedDarkBlueClusterIcons',
            
            // Группировка близких меток
            groupByCoordinates: false,
            clusterDisableClickZoom: false,
            clusterHideIconOnBalloonOpen: false,
            geoObjectHideIconOnBalloonOpen: false,
            minClusterSize: 3,
            gridSize: 80
        });
        
        loadRinksOnMap();
    });
}

async function loadRinksOnMap(filters = {}) {
    if (!map || !clusterer) return;

    const result = await API.getRinks(filters);
    if (!result.success) return;
    
    clusterer.removeAll();
    map.geoObjects.remove(clusterer);
    
    const rinks = result.data.results || [];
    const placemarks = [];
    
    rinks.forEach(rink => {
        if (rink.latitude && rink.longitude) {
            const placemark = new ymaps.Placemark(
                [rink.latitude, rink.longitude],
                {
                    // Данные для балуна (всплывающее окно при клике)
                    balloonContentHeader: `<strong>${rink.name}</strong>`,
                    balloonContentBody: `
                        <div style="max-width: 250px;">
                            <p style="margin: 5px 0; color: #666;">${rink.address || 'Адрес не указан'}</p>
                            <p style="margin: 5px 0;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; 
                                    background: ${rink.is_paid ? '#ffc107' : '#28a745'}; color: ${rink.is_paid ? '#000' : '#fff'};">
                                    ${rink.is_paid ? 'Платный' : 'Бесплатно'}
                                </span>
                            </p>
                            <p style="margin: 5px 0; font-size: 12px; color: #888;">
                                ${rink.district || ''}
                            </p>
                        </div>
                    `,
                    balloonContentFooter: `<a href="rink.html?id=${rink.id}" style="color: #007bff;">Подробнее →</a>`,
                    hintContent: rink.name,
                    // Для статистики кластера
                    clusterCaption: rink.name,
                    rinkData: rink // Сохраняем данные катка
                },
                {
                    // Иконка: зеленая для бесплатных, синяя для платных
                    preset: rink.is_paid ? 'islands#blueCircleDotIcon' : 'islands#greenCircleDotIcon'
                }
            );
            
            placemarks.push(placemark);
        }
    });
    
    // Добавляем все метки в кластеризатор
    clusterer.add(placemarks);
    
    // Добавляем кластеризатор на карту
    map.geoObjects.add(clusterer);
    
    // Подстраиваем масштаб под все метки
    if (placemarks.length > 0) {
        map.setBounds(clusterer.getBounds(), {
            checkZoomRange: true,
            zoomMargin: 40
        });
    }
}

/**
 * Обновление меток друзей на карте (для группового поиска)
 */
function updateGroupPlacemarks(points) {
    // Эта функция остается без изменений
    if (!map) return;
    
    // Удаляем старые метки друзей (если есть)
    if (window.groupPlacemarks) {
        window.groupPlacemarks.forEach(pm => map.geoObjects.remove(pm));
    }
    window.groupPlacemarks = [];
    
    points.forEach((point, index) => {
        const placemark = new ymaps.Placemark(
            [point.latitude, point.longitude],
            {
                balloonContent: `<strong>Точка ${index + 1}</strong><br>Координаты: ${point.latitude.toFixed(4)}, ${point.longitude.toFixed(4)}`,
                hintContent: `Участник ${index + 1}`
            },
            {
                preset: 'islands#redPersonIcon' // Красные иконки-человечки для участников
            }
        );
        map.geoObjects.add(placemark);
        window.groupPlacemarks.push(placemark);
    });
}
