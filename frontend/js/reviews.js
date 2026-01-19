// Работа с отзывами
async function loadReviews() {
    const urlParams = new URLSearchParams(window.location.search);
    const rinkId = urlParams.get('id');
    if (!rinkId) return;
    
    const result = await API.getReviews(rinkId);
    if (!result.success) return;
    
    const container = document.getElementById('reviewsList');
    if (!container) return;
    
    const reviews = result.data || [];
    if (reviews.length === 0) {
        container.innerHTML = '<p class="text-muted">Пока нет отзывов</p>';
        return;
    }
    
    let html = '';
    reviews.forEach(review => {
        const score = (review.upvotes_count || 0) - (review.downvotes_count || 0);
        const scoreClass = score > 0 ? 'text-success' : score < 0 ? 'text-danger' : 'text-muted';
        
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Оценка: ${review.rating}/5</h6>
                            <p>${review.text || ''}</p>
                            <small class="text-muted">
                                ${review.ice_condition ? 'Лёд: ' + getIceConditionText(review.ice_condition) : ''}
                                ${review.ice_condition && review.crowd_level ? ' | ' : ''}
                                ${review.crowd_level ? 'Загруженность: ' + getCrowdLevelText(review.crowd_level) : ''}
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="mb-2">
                                <button class="btn btn-sm btn-outline-success" onclick="vote(${review.id}, 'upvote')">↑</button>
                                <span class="${scoreClass} mx-2">${score > 0 ? '+' : ''}${score}</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="vote(${review.id}, 'downvote')">↓</button>
                            </div>
                            ${review.photo_url ? `<img src="${review.photo_url}" class="img-thumbnail" style="max-width: 100px;">` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function getIceConditionText(condition) {
    const map = {
        'excellent': 'Отличное',
        'good': 'Хорошее',
        'fair': 'Среднее',
        'poor': 'Плохое'
    };
    return map[condition] || condition;
}

function getCrowdLevelText(level) {
    const map = {
        'low': 'Низкая',
        'medium': 'Средняя',
        'high': 'Высокая'
    };
    return map[level] || level;
}

async function handleReviewSubmit(e) {
    e.preventDefault();
    
    if (!Auth.isLoggedIn()) {
        alert('Войдите в систему');
        return;
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const rinkId = urlParams.get('id');
    
    if (!rinkId) {
        alert('Ошибка: не указан ID катка');
        return;
    }
    
    const reviewData = {
        rink_id: rinkId,
        rating: parseInt(document.getElementById('reviewRating').value),
        text: document.getElementById('reviewText').value.trim()
    };
    
    // Опциональные поля
    const iceCondition = document.getElementById('iceCondition').value;
    if (iceCondition) {
        reviewData.ice_condition = iceCondition;
    }
    
    const crowdLevel = document.getElementById('crowdLevel').value;
    if (crowdLevel) {
        reviewData.crowd_level = crowdLevel;
    }
    
    if (!reviewData.text) {
        alert('Введите текст отзыва');
        return;
    }
    
    const photoInput = document.getElementById('reviewPhoto');
    if (photoInput && photoInput.files.length > 0) {
        // Загрузка фото (упрощенная версия - просто сохраняем путь)
        reviewData.photo_path = 'uploads/reviews/' + Date.now() + '_' + photoInput.files[0].name;
        reviewData.photo_url = reviewData.photo_path; // В реальном проекте здесь будет загрузка на сервер
    }
    
    try {
        const result = await API.createReview(null, reviewData);
        if (result.success) {
            alert('Отзыв добавлен!');
            loadReviews();
            e.target.reset();
        } else {
            alert('Ошибка: ' + (result.message || result.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка при отправке отзыва: ' + error.message);
    }
}

async function vote(reviewId, voteType) {
    if (!Auth.isLoggedIn()) {
        alert('Войдите в систему для голосования');
        return;
    }
    
    const result = await API.vote(reviewId, voteType);
    if (result.success) {
        loadReviews();
    } else {
        alert(result.message);
    }
}
