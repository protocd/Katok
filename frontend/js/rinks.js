// Работа с катками
function displayRinks(rinks) {
    const container = document.getElementById('rinksList');
    if (!container) return;
    
    if (!rinks || rinks.length === 0) {
        container.innerHTML = '<div class="col-12"><p class="text-center text-muted">Катки не найдены</p></div>';
        return;
    }
    
    let html = '';
    rinks.forEach(rink => {
        const badge = rink.is_paid ? '<span class="badge bg-warning">Платный</span>' : '<span class="badge bg-success">Бесплатный</span>';
        html += `
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${rink.name}</h5>
                        <p class="card-text text-muted">${rink.address || ''}</p>
                        <p>${badge} ${rink.district ? '<span class="badge bg-secondary">' + rink.district + '</span>' : ''}</p>
                        <a href="rink.html?id=${rink.id}" class="btn btn-primary">Подробнее</a>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

async function loadRinks(filters = {}) {
    const result = await API.getRinks(filters);
    if (result.success) {
        displayRinks(result.data.results || []);
    } else {
        document.getElementById('rinksList').innerHTML = 
            '<div class="col-12"><p class="text-danger">Ошибка загрузки: ' + result.message + '</p></div>';
    }
}

async function loadDistricts() {
    const result = await API.getRinks();
    if (result.success) {
        const districts = [...new Set(result.data.results.map(r => r.district).filter(Boolean))];
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
