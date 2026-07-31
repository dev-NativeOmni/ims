import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function updateCsrfTokens(newToken) {
    if (!newToken) return;

    // Update meta tag
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        metaToken.setAttribute('content', newToken);
    }

    // Update Axios default header
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;

    // Update all hidden _token form inputs on the current page
    document.querySelectorAll('input[name="_token"]').forEach(input => {
        input.value = newToken;
    });
}

const initialCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (initialCsrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = initialCsrfToken;
} else {
    console.error('CSRF token meta tag not found!');
}

/*
|--------------------------------------------------------------------------
| Session Keep-Alive & CSRF Token Auto-Refresh
|--------------------------------------------------------------------------
| Prevent Error 419 (Page Expired) by pinging /keep-alive periodically
| and whenever the user returns/focuses on the browser tab.
*/
let lastKeepAliveTime = Date.now();

async function pingKeepAlive() {
    try {
        const response = await window.axios.get('/keep-alive', {
            headers: { 'Cache-Control': 'no-cache' }
        });
        if (response.data && response.data.csrf) {
            updateCsrfTokens(response.data.csrf);
            lastKeepAliveTime = Date.now();
        }
    } catch (error) {
        // Silent catch if user is logged out or offline
    }
}

// Ping every 10 minutes (600,000 ms)
setInterval(pingKeepAlive, 10 * 60 * 1000);

// Ping when user returns to tab if more than 3 minutes have passed
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        const elapsed = Date.now() - lastKeepAliveTime;
        if (elapsed > 3 * 60 * 1000) {
            pingKeepAlive();
        }
    }
});

// Handle Axios 419 response gracefully
window.axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response && error.response.status === 419) {
            console.warn('Session 419 detected. Attempting CSRF token refresh...');
            await pingKeepAlive();
        }
        return Promise.reject(error);
    }
);
