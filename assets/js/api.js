/**
 * API Client for SKD CAT-BKN Application
 * 
 * Provides standardized HTTP request handling with:
 * - CSRF token management
 * - Error handling
 * - Request/response interceptors
 * - Base URL configuration
 */
class ApiClient {
    constructor(baseUrl = '/permen/api') {
        this.baseUrl = baseUrl;
        this.csrfToken = this.getCsrfToken();
        
        // Default request options
        this.defaultOptions = {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        // Error messages in Indonesian
        this.errorMessages = {
            400: 'Permintaan tidak valid. Silakan periksa data yang dikirim.',
            401: 'Sesi telah berakhir. Silakan login kembali.',
            403: 'Akses ditolak. Anda tidak memiliki izin.',
            404: 'Data tidak ditemukan.',
            422: 'Validasi gagal. Silakan periksa input Anda.',
            429: 'Terlalu banyak permintaan. Silakan tunggu sebentar.',
            500: 'Terjadi kesalahan server. Silakan coba lagi nanti.',
            502: 'Server sementara tidak tersedia.',
            503: 'Layanan sementara tidak tersedia.',
            0: 'Koneksi terputus. Periksa internet Anda.'
        };
    }
    
    /**
     * Get CSRF token from meta tag or session
     */
    getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag?.content || window.csrfToken || '';
    }
    
    /**
     * Update CSRF token
     */
    setCsrfToken(token) {
        this.csrfToken = token;
        this.defaultOptions.headers['X-CSRF-Token'] = token;
    }
    
    /**
     * Main request method
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint}`;
        
        // Merge options
        const mergedOptions = {
            ...this.defaultOptions,
            ...options,
            headers: {
                ...this.defaultOptions.headers,
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(url, mergedOptions);
            
            // Parse JSON response
            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = { message: await response.text() };
            }
            
            // Handle HTTP errors
            if (!response.ok) {
                const error = new ApiError(
                    data.message || this.errorMessages[response.status] || `HTTP ${response.status}`,
                    response.status,
                    data.error?.details || {},
                    data.error?.code || `ERROR_${response.status}`
                );
                error.response = data;
                throw error;
            }
            
            return data;
            
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            
            // Network error
            throw new ApiError(
                this.errorMessages[0],
                0,
                {},
                'NETWORK_ERROR'
            );
        }
    }
    
    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url, { method: 'GET' });
    }
    
    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        const isFormData = data instanceof FormData;
        
        return this.request(endpoint, {
            method: 'POST',
            headers: isFormData ? {} : { 'Content-Type': 'application/json' },
            body: isFormData ? data : JSON.stringify(data)
        });
    }
    
    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    }
    
    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
    
    // ==================== API Endpoints ====================
    
    /**
     * Tryout APIs
     */
    async getSoal(sessionId) {
        return this.get('get_soal.php', { session_id: sessionId });
    }
    
    async submitJawaban(answerId, jawaban, isRagu = false) {
        return this.post('submit_jawaban.php', {
            answer_id: answerId,
            jawaban: jawaban,
            is_ragu: isRagu
        });
    }
    
    async finishTryout(sessionId) {
        return this.post('finish_tryout.php', { session_id: sessionId });
    }
    
    async pauseTryout(sessionId) {
        return this.post('pause_tryout.php', { session_id: sessionId });
    }
    
    async resumeTryout(sessionId) {
        return this.post('resume_tryout.php', { session_id: sessionId });
    }
    
    async nextSubtes(sessionId, currentSubtes, nextSubtes) {
        return this.post('next_subtes.php', {
            session_id: sessionId,
            current_subtes: currentSubtes,
            next_subtes: nextSubtes
        });
    }
    
    /**
     * User APIs
     */
    async getNotifications(limit = 10) {
        return this.get('get_notifications.php', { limit: limit });
    }
    
    async markNotificationRead(notificationId) {
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        return this.post('mark_notification_read.php', formData);
    }
    
    async submitFeedback(category, message) {
        return this.post('submit_feedback.php', {
            category: category,
            message: message
        });
    }
    
    async toggleBookmark(questionId) {
        return this.post('toggle_bookmark.php', { question_id: questionId });
    }
    
    /**
     * Daily Quiz APIs
     */
    async getDailyQuiz() {
        return this.get('get_daily_quiz.php');
    }
    
    async submitDailyAnswer(questionId, jawaban) {
        return this.post('submit_daily_answer.php', {
            question_id: questionId,
            jawaban: jawaban
        });
    }
    
    async finishDailyQuiz() {
        return this.post('finish_daily_quiz.php');
    }
}

/**
 * API Error Class
 */
class ApiError extends Error {
    constructor(message, status, details = {}, code = '') {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.details = details;
        this.code = code;
    }
    
    /**
     * Check if error is authentication related
     */
    isAuthError() {
        return this.status === 401;
    }
    
    /**
     * Check if error is validation related
     */
    isValidationError() {
        return this.status === 422;
    }
    
    /**
     * Check if error is rate limit
     */
    isRateLimit() {
        return this.status === 429;
    }
    
    /**
     * Check if error is server error
     */
    isServerError() {
        return this.status >= 500;
    }
    
    /**
     * Check if error is network error
     */
    isNetworkError() {
        return this.status === 0;
    }
}

// Create global instance
window.api = new ApiClient();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ApiClient, ApiError };
}
