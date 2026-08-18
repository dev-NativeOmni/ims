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
| and whenever the user returns/focuses/wakes the browser tab (iOS & Android).
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
        // If 401 Unauthorized or 419 Page Expired occurs during ping, redirect to login
        const status = error.response ? error.response.status : 0;
        const isAuthPage = window.location.pathname.startsWith('/login') || window.location.pathname.startsWith('/register');
        if ((status === 401 || status === 419) && !isAuthPage) {
            console.warn('Session expired overnight. Redirecting to login...');
            window.location.href = '/login';
        }
    }
}

// Ping every 5 minutes (300,000 ms)
setInterval(pingKeepAlive, 5 * 60 * 1000);

// Ping when user returns to tab or wakes mobile browser from sleep (pageshow, focus, visibilitychange)
['visibilitychange', 'pageshow', 'focus'].forEach(eventType => {
    window.addEventListener(eventType, (event) => {
        if (eventType === 'visibilitychange' && document.visibilityState !== 'visible') return;
        if (eventType === 'pageshow' && event.persisted) {
            pingKeepAlive();
            return;
        }
        const elapsed = Date.now() - lastKeepAliveTime;
        if (elapsed > 2 * 60 * 1000) {
            pingKeepAlive();
        }
    });
});

// Handle Axios 419 / 401 response gracefully
window.axios.interceptors.response.use(
    response => response,
    async error => {
        const status = error.response ? error.response.status : 0;
        const isAuthPage = window.location.pathname.startsWith('/login') || window.location.pathname.startsWith('/register');
        if ((status === 419 || status === 401) && !isAuthPage) {
            console.warn('Session 419/401 detected. Redirecting to login page...');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);
