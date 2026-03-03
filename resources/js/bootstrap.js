import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Global Axios error interceptor.
 *
 * Catches unhandled AJAX failures and displays a user-friendly message
 * instead of silently failing. Individual .catch() handlers take priority.
 */
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        // Let callers with their own .catch() handle it
        if (error.__handled) {
            return Promise.reject(error);
        }

        const status = error.response?.status;
        const data = error.response?.data;

        if (status === 419) {
            // CSRF token expired — session likely timed out
            if (confirm('Sesi Anda telah berakhir. Muat ulang halaman?')) {
                window.location.reload();
            }
            return Promise.reject(error);
        }

        if (status === 403) {
            alert(data?.message || 'Anda tidak memiliki izin untuk melakukan aksi ini.');
            return Promise.reject(error);
        }

        if (status === 422) {
            // Validation error — let the caller handle it if they have a .catch()
            return Promise.reject(error);
        }

        if (status === 429) {
            alert('Terlalu banyak permintaan. Silakan tunggu sebentar.');
            return Promise.reject(error);
        }

        if (status >= 500) {
            console.error('[SAKUMI] Server error:', status, data);
            alert('Terjadi kesalahan pada server. Silakan coba lagi atau hubungi administrator.');
            return Promise.reject(error);
        }

        if (!error.response) {
            // Network error — server unreachable
            console.error('[SAKUMI] Network error:', error.message);
            alert('Koneksi ke server terputus. Periksa koneksi internet Anda.');
            return Promise.reject(error);
        }

        return Promise.reject(error);
    }
);
