// Работа с отзывами
async function loadReviews() {
    const urlParams = new URLSearchParams(window.location.search);
    const rinkId = urlParams.get('id');
    if (!rinkId) return;
    
    const result = await API.getReviews(rinkId);
    if (!result.success) return;
    
    const container = document.getElementById('reviewsList');
    if (!container) return;
    
    // Проверяем структуру ответа (может быть массив или объект)
    let reviews = [];
    let hasUserReview = false;
    let userReviewId = null;
    
    if (Array.isArray(result.data)) {
        // Старый формат - просто массив отзывов
        reviews = result.data;
    } else if (result.data && result.data.reviews) {
        // Новый формат - объект с reviews, has_user_review, user_review_id
        reviews = result.data.reviews || [];
        hasUserReview = result.data.has_user_review || false;
        userReviewId = result.data.user_review_id || null;
    } else {
        reviews = [];
    }
    
    // Скрываем/показываем форму создания отзыва
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        if (hasUserReview && Auth.isLoggedIn()) {
            // Если у пользователя уже есть отзыв - скрываем форму
            reviewForm.classList.add('d-none');
        } else if (Auth.isLoggedIn()) {
            // Если пользователь авторизован и отзыва нет - показываем форму
            reviewForm.classList.remove('d-none');
        } else {
            // Если не авторизован - скрываем форму
            reviewForm.classList.add('d-none');
        }
    }
    
    if (reviews.length === 0) {
        container.innerHTML = '<p class="text-muted">Пока нет отзывов</p>';
        return;
    }
    
    const currentUser = Auth.getUser();
    
    let html = '';
    reviews.forEach(review => {
        const score = (review.upvotes_count || 0) - (review.downvotes_count || 0);
        const scoreClass = score > 0 ? 'text-success' : score < 0 ? 'text-danger' : 'text-muted';
        const canEdit = review.can_edit || false;
        
        html += `
            <div class="card mb-3" id="review-${review.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div style="flex: 1;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6>Оценка: ${review.rating}/5</h6>
                                ${canEdit ? `
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editReview(${review.id})">✏️ Редактировать</button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteReview(${review.id})">🗑️ Удалить</button>
                                    </div>
                                ` : ''}
                            </div>
                            <div id="review-content-${review.id}">
                                <p>${review.text || ''}</p>
                                <small class="text-muted">
                                    ${review.ice_condition ? 'Лёд: ' + getIceConditionText(review.ice_condition) : ''}
                                    ${review.ice_condition && review.crowd_level ? ' | ' : ''}
                                    ${review.crowd_level ? 'Загруженность: ' + getCrowdLevelText(review.crowd_level) : ''}
                                </small>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <div class="mb-2">
                                <button class="btn btn-sm btn-outline-success" onclick="vote(${review.id}, 'up')">↑</button>
                                <span class="${scoreClass} mx-2">${score > 0 ? '+' : ''}${score}</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="vote(${review.id}, 'down')">↓</button>
                            </div>
                            ${review.photo_url ? `
                                <div class="mt-2">
                                    <img src="${review.photo_url}" 
                                         class="img-thumbnail" 
                                         style="max-width: 200px; max-height: 200px; object-fit: cover; cursor: pointer;"
                                         onclick="window.open('${review.photo_url}', '_blank')"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                         alt="Фото отзыва">
                                    <div style="display: none; color: red; font-size: 0.8em;">Ошибка загрузки фото</div>
                                </div>
                            ` : ''}
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
        try {
            const uploadResult = await API.uploadPhoto(photoInput.files[0]);
            
            if (uploadResult.success && uploadResult.data && uploadResult.data.url) {
                reviewData.photo_url = uploadResult.data.url;
            } else {
                alert('Ошибка загрузки фото: ' + (uploadResult.message || 'Неизвестная ошибка'));
            }
        } catch (error) {
            alert('Ошибка при загрузке фото: ' + error.message);
        }
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
    
    if (voteType === 'upvote') voteType = 'up';
    if (voteType === 'downvote') voteType = 'down';
    
    if (voteType !== 'up' && voteType !== 'down') {
        alert('Ошибка: неверный тип голоса');
        return;
    }
    
    try {
        const result = await API.vote(reviewId, voteType);
        if (result.success) {
            loadReviews();
        } else {
            alert('Ошибка: ' + (result.message || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка при голосовании: ' + error.message);
    }
}

async function editReview(reviewId) {
    try {
        if (!Auth.isLoggedIn()) {
            alert('Войдите в систему');
            return;
        }
        
        // Получаем данные отзыва
        const urlParams = new URLSearchParams(window.location.search);
        const rinkId = urlParams.get('id');
        if (!rinkId) {
            alert('Ошибка: не указан ID катка');
            return;
        }
        
        const reviewsResult = await API.getReviews(rinkId);
        if (!reviewsResult.success) {
            alert('Ошибка загрузки отзыва: ' + (reviewsResult.message || 'Неизвестная ошибка'));
            return;
        }
        
        // Обрабатываем структуру ответа (может быть массив или объект)
        let reviews = [];
        if (Array.isArray(reviewsResult.data)) {
            reviews = reviewsResult.data;
        } else if (reviewsResult.data && reviewsResult.data.reviews) {
            reviews = reviewsResult.data.reviews || [];
        }
        
        const review = reviews.find(r => r.id == reviewId);
        if (!review) {
            alert('Отзыв не найден');
            return;
        }
        
        const contentDiv = document.getElementById(`review-content-${reviewId}`);
        if (!contentDiv) {
            alert('Ошибка: элемент формы не найден');
            return;
        }
    
    contentDiv.innerHTML = `
        <form onsubmit="saveReviewEdit(event, ${reviewId})">
            <div class="mb-2">
                <label class="form-label small">Оценка</label>
                <select class="form-select form-select-sm" id="edit-rating-${reviewId}" required>
                    <option value="5" ${review.rating == 5 ? 'selected' : ''}>5 - Отлично</option>
                    <option value="4" ${review.rating == 4 ? 'selected' : ''}>4 - Хорошо</option>
                    <option value="3" ${review.rating == 3 ? 'selected' : ''}>3 - Нормально</option>
                    <option value="2" ${review.rating == 2 ? 'selected' : ''}>2 - Плохо</option>
                    <option value="1" ${review.rating == 1 ? 'selected' : ''}>1 - Ужасно</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label small">Состояние льда</label>
                <select class="form-select form-select-sm" id="edit-ice-${reviewId}">
                    <option value="">Не указано</option>
                    <option value="excellent" ${review.ice_condition == 'excellent' ? 'selected' : ''}>Отличное</option>
                    <option value="good" ${review.ice_condition == 'good' ? 'selected' : ''}>Хорошее</option>
                    <option value="fair" ${review.ice_condition == 'fair' ? 'selected' : ''}>Среднее</option>
                    <option value="poor" ${review.ice_condition == 'poor' ? 'selected' : ''}>Плохое</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label small">Загруженность</label>
                <select class="form-select form-select-sm" id="edit-crowd-${reviewId}">
                    <option value="">Не указано</option>
                    <option value="low" ${review.crowd_level == 'low' ? 'selected' : ''}>Низкая</option>
                    <option value="medium" ${review.crowd_level == 'medium' ? 'selected' : ''}>Средняя</option>
                    <option value="high" ${review.crowd_level == 'high' ? 'selected' : ''}>Высокая</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label small">Текст отзыва</label>
                <textarea class="form-control form-control-sm" id="edit-text-${reviewId}" rows="3" required>${review.text || ''}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Сохранить</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEdit(${reviewId})">Отмена</button>
            </div>
        </form>
    `;
    } catch (error) {
        alert('Ошибка при редактировании отзыва: ' + error.message);
    }
}

function cancelEdit(reviewId) {
    loadReviews();
}

async function saveReviewEdit(e, reviewId) {
    e.preventDefault();
    
    const data = {
        rating: parseInt(document.getElementById(`edit-rating-${reviewId}`).value),
        text: document.getElementById(`edit-text-${reviewId}`).value.trim(),
        ice_condition: document.getElementById(`edit-ice-${reviewId}`).value || null,
        crowd_level: document.getElementById(`edit-crowd-${reviewId}`).value || null
    };
    
    if (!data.text || data.text.length < 10) {
        alert('Текст отзыва должен содержать минимум 10 символов');
        return;
    }
    
    try {
        const result = await API.updateReview(reviewId, data);
        if (result.success) {
            // Перезагружаем отзывы (форма останется скрытой, т.к. отзыв уже есть)
            loadReviews();
        } else {
            alert('Ошибка: ' + (result.message || result.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка при сохранении: ' + error.message);
    }
}

async function deleteReview(reviewId) {
    if (!confirm('Вы уверены, что хотите удалить этот отзыв?')) {
        return;
    }
    
    try {
        const result = await API.deleteReview(reviewId);
        if (result.success) {
            // После удаления отзыва форма создания снова появится
            loadReviews();
        } else {
            alert('Ошибка: ' + (result.message || result.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка при удалении: ' + error.message);
    }
}

// Делаем функции доступными глобально для вызова из HTML
window.editReview = editReview;
window.deleteReview = deleteReview;
window.saveReviewEdit = saveReviewEdit;
window.cancelEdit = cancelEdit;
window.vote = vote;
window.handleReviewSubmit = handleReviewSubmit;
