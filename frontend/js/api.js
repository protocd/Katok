// API клиент для работы с backend
const API = {
    baseUrl: 'http://localhost:8080/rinks-moscow-app/backend/api',
    
    // Общий метод для запросов
    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(this.baseUrl + endpoint, options);
            
            // Проверяем, что ответ JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                return { 
                    success: false, 
                    message: 'Ошибка сервера: ' + (text.substring(0, 200) || 'Неверный формат ответа')
                };
            }
            
            const result = await response.json();
            
            // Если ответ не успешный, но есть message или error
            if (!result.success && !result.message && result.error) {
                result.message = result.error;
            }
            
            return result;
        } catch (error) {
            return { success: false, message: 'Ошибка сети: ' + error.message };
        }
    },
    
    // Катки
    async getRinks(filters = {}) {
        let url = '/rinks.php?';
        if (filters.search) url += 'search=' + encodeURIComponent(filters.search) + '&';
        if (filters.district) url += 'district=' + encodeURIComponent(filters.district) + '&';
        if (filters.is_paid !== undefined) url += 'is_paid=' + (filters.is_paid ? '1' : '0') + '&';
        
        // Все дополнительные фильтры оборудования
        const equipmentParams = [
            'has_equipment_rental', 'has_locker_room', 'has_cafe', 
            'has_wifi', 'has_atm', 'has_medpoint', 'is_disabled_accessible'
        ];
        
        equipmentParams.forEach(param => {
            if (filters[param] === true) {
                url += param + '=1&';
            }
        });
        
        return await this.request(url);
    },
    
    async getRink(id) {
        return await this.request('/rinks.php?id=' + id);
    },
    
    // Авторизация
    async register(email, password, name) {
        return await this.request('/auth/register.php', 'POST', { email, password, name });
    },
    
    async login(email, password) {
        const result = await this.request('/auth/login.php', 'POST', { email, password });
        if (result.success) {
            localStorage.setItem('user', JSON.stringify(result.data.user));
        }
        return result;
    },
    
    async logout() {
        await this.request('/auth/logout.php', 'POST');
        localStorage.removeItem('user');
    },
    
    async getCurrentUser() {
        return await this.request('/auth/user.php');
    },
    
    // Отзывы
    async getReviews(rinkId) {
        return await this.request('/reviews.php?rink_id=' + rinkId);
    },
    
    async createReview(visitId, data) {
        const payload = { ...data };
        if (visitId) {
            payload.visit_id = visitId;
        }
        return await this.request('/reviews.php', 'POST', payload);
    },
    
    async updateReview(reviewId, data) {
        const payload = { id: reviewId, ...data, _method: 'PUT' };
        return await this.request('/reviews.php', 'POST', payload);
    },
    
    async deleteReview(reviewId) {
        const payload = { _method: 'DELETE' };
        return await this.request('/reviews.php?id=' + reviewId, 'POST', payload);
    },
    
    // Чек-ины
    async checkin(rinkId, latitude, longitude) {
        return await this.request('/checkins.php', 'POST', {
            rink_id: rinkId,
            latitude: latitude,
            longitude: longitude
        });
    },
    
    // Голосование
    async vote(reviewId, voteType) {
        return await this.request('/votes.php', 'POST', {
            review_id: reviewId,
            vote_type: voteType
        });
    },
    
    // События
    async getEvents(rinkId) {
        return await this.request('/events.php?rink_id=' + rinkId);
    },
    
    async createEvent(rinkId, title, description, eventDate, eventTime) {
        return await this.request('/events.php', 'POST', {
            rink_id: rinkId,
            title: title,
            description: description,
            event_date: eventDate,
            event_time: eventTime
        });
    },
    
    async joinEvent(eventId) {
        return await this.request('/events/join.php', 'POST', { event_id: eventId });
    },
    
    // Статистика
    async getStats(rinkId) {
        return await this.request('/stats.php?rink_id=' + rinkId);
    }
};
