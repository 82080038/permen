/**
 * Common JS — SKD CAT-BKN
 * Fungsi reusable vanilla JS untuk seluruh aplikasi
 */

/**
 * Global error handler for JavaScript errors
 */
window.addEventListener('error', function (event) {
    console.error('JavaScript Error:', event.error);

    // Log error to server if available
    if (typeof logErrorToServer === 'function') {
        logErrorToServer({
            message: event.error.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            stack: event.error.stack
        });
    }

    // Show user-friendly error message
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = 'background:#f8d7da;color:#721c24;padding:1rem;border-radius:5px;margin:1rem;position:fixed;top:10px;right:10px;z-index:9999;max-width:300px;';
    errorDiv.innerHTML = '<strong>Terjadi kesalahan.</strong> Silakan refresh halaman.';
    document.body.appendChild(errorDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => errorDiv.remove(), 5000);
});

/**
 * Global unhandled promise rejection handler
 */
window.addEventListener('unhandledrejection', function (event) {
    console.error('Unhandled Promise Rejection:', event.reason);

    // Log error to server if available
    if (typeof logErrorToServer === 'function') {
        logErrorToServer({
            message: 'Unhandled Promise Rejection',
            reason: event.reason?.toString() || 'Unknown reason',
            stack: event.reason?.stack || ''
        });
    }
});

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: 'success', 'error', 'info', 'warning'
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
function showToast(message, type = 'info', duration = 3000) {
    // Remove existing toast
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) existingToast.remove();

    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'toast-notification';

    // Set colors based on type
    const colors = {
        success: { bg: '#d4edda', color: '#155724', icon: '✓' },
        error: { bg: '#f8d7da', color: '#721c24', icon: '✕' },
        warning: { bg: '#fff3cd', color: '#856404', icon: '⚠' },
        info: { bg: '#d1ecf1', color: '#0c5460', icon: 'ℹ' }
    };
    const style = colors[type] || colors.info;

    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${style.bg};
        color: ${style.color};
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 350px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideIn 0.3s ease-out;
        font-size: 0.9rem;
    `;

    toast.innerHTML = `<span style="font-size: 1.2rem; font-weight: bold;">${style.icon}</span><span>${escapeHtml(message)}</span>`;

    // Add animation keyframes if not exists
    if (!document.querySelector('#toast-animations')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'toast-animations';
        styleSheet.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(styleSheet);
    }

    document.body.appendChild(toast);

    // Auto-remove after duration
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/**
 * Escape HTML untuk mencegah XSS saat render konten dinamis
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Toggle accordion card (materi.php)
 */
function toggle(el) {
    const body = el.nextElementSibling;
    const icon = el.querySelector('.toggle-icon');
    if (body.classList.contains('active')) {
        body.classList.remove('active');
        if (icon) icon.textContent = '+';
    } else {
        body.classList.add('active');
        if (icon) icon.textContent = '−';
    }
}

/**
 * Validate password strength
 * @param {string} password - Password to validate
 * @returns {object} {valid: boolean, message: string}
 */
function validatePasswordStrength(password) {
    if (password.length < 8) {
        return { valid: false, message: 'Password minimal 8 karakter' };
    }
    if (!/[A-Z]/.test(password)) {
        return { valid: false, message: 'Password harus mengandung minimal 1 huruf besar' };
    }
    if (!/[a-z]/.test(password)) {
        return { valid: false, message: 'Password harus mengandung minimal 1 huruf kecil' };
    }
    if (!/[0-9]/.test(password)) {
        return { valid: false, message: 'Password harus mengandung minimal 1 angka' };
    }
    return { valid: true, message: '' };
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean}
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Show error message for form field
 * @param {HTMLElement} field - The input field
 * @param {string} message - Error message
 */
function showFieldError(field, message) {
    // Remove existing error
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) existingError.remove();

    // Add error styling
    field.style.borderColor = '#e74c3c';
    field.style.backgroundColor = '#fef5f5';

    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: #e74c3c;
        font-size: 0.8rem;
        margin-top: 0.3rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        animation: fadeIn 0.2s ease-out;
    `;
    errorDiv.innerHTML = `<span style="font-weight: bold;">⚠</span><span>${escapeHtml(message)}</span>`;
    field.parentElement.appendChild(errorDiv);

    // Add animation keyframes if not exists
    if (!document.querySelector('#field-error-animations')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'field-error-animations';
        styleSheet.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(styleSheet);
    }
}

/**
 * Clear error message for form field
 * @param {HTMLElement} field - The input field
 */
function clearFieldError(field) {
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) existingError.remove();
    field.style.borderColor = '#ddd';
    field.style.backgroundColor = '#fff';
}

/**
 * Show confirmation dialog
 * @param {string} message - Confirmation message
 * @param {string} title - Dialog title (default: 'Konfirmasi')
 * @returns {Promise<boolean>} - User's choice (true = confirm, false = cancel)
 */
function confirmDialog(message, title = 'Konfirmasi') {
    return new Promise((resolve) => {
        // Remove existing dialog
        const existingDialog = document.querySelector('.confirm-dialog-overlay');
        if (existingDialog) existingDialog.remove();

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'confirm-dialog-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10001;
            animation: fadeIn 0.2s ease-out;
        `;

        // Create dialog
        const dialog = document.createElement('div');
        dialog.className = 'confirm-dialog';
        dialog.style.cssText = `
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;

        dialog.innerHTML = `
            <h3 style="margin: 0 0 1rem 0; color: #1a5276; font-size: 1.1rem;">${escapeHtml(title)}</h3>
            <p style="margin: 0 0 1.5rem 0; color: #555; line-height: 1.5;">${escapeHtml(message)}</p>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button class="confirm-cancel" style="
                    padding: 0.6rem 1.2rem;
                    border: 1px solid #ddd;
                    background: #fff;
                    color: #555;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 0.9rem;
                ">Batal</button>
                <button class="confirm-ok" style="
                    padding: 0.6rem 1.2rem;
                    border: none;
                    background: #e74c3c;
                    color: #fff;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 0.9rem;
                ">Ya, Lanjutkan</button>
            </div>
        `;

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        // Add animation keyframes if not exists
        if (!document.querySelector('#confirm-dialog-animations')) {
            const styleSheet = document.createElement('style');
            styleSheet.id = 'confirm-dialog-animations';
            styleSheet.textContent = `
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(styleSheet);
        }

        // Handle button clicks
        dialog.querySelector('.confirm-cancel').addEventListener('click', () => {
            overlay.remove();
            resolve(false);
        });

        dialog.querySelector('.confirm-ok').addEventListener('click', () => {
            overlay.remove();
            resolve(true);
        });

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
                resolve(false);
            }
        });

        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                overlay.remove();
                resolve(false);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    });
}

/**
 * Dark mode toggle
 */
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
}

/**
 * Show skeleton loading for a container
 * @param {HTMLElement} container - Container to show skeleton in
 * @param {number} count - Number of skeleton items to show
 */
function showSkeletonLoading(container, count = 3) {
    const skeletonHTML = Array(count).fill(0).map(() => `
        <div class="skeleton-card" style="
            background: #f0f0f0;
            border-radius: 8px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            animation: skeleton-pulse 1.5s ease-in-out infinite;
        ">
            <div class="skeleton-title" style="
                height: 20px;
                background: #e0e0e0;
                border-radius: 4px;
                margin-bottom: 0.8rem;
                width: 60%;
            "></div>
            <div class="skeleton-line" style="
                height: 14px;
                background: #e0e0e0;
                border-radius: 4px;
                margin-bottom: 0.5rem;
                width: 100%;
            "></div>
            <div class="skeleton-line" style="
                height: 14px;
                background: #e0e0e0;
                border-radius: 4px;
                margin-bottom: 0.5rem;
                width: 80%;
            "></div>
            <div class="skeleton-line" style="
                height: 14px;
                background: #e0e0e0;
                border-radius: 4px;
                width: 40%;
            "></div>
        </div>
    `).join('');

    container.innerHTML = skeletonHTML;

    // Add animation keyframes if not exists
    if (!document.querySelector('#skeleton-animations')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'skeleton-animations';
        styleSheet.textContent = `
            @keyframes skeleton-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
        `;
        document.head.appendChild(styleSheet);
    }
}

/**
 * Hide skeleton loading and show content
 * @param {HTMLElement} container - Container to update
 * @param {string} content - HTML content to show
 */
function hideSkeletonLoading(container, content) {
    container.innerHTML = content;
}

/**
 * Initialize mobile menu
 */
function initMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            hamburger.setAttribute('aria-expanded', mobileMenu.classList.contains('active'));
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
                hamburger.focus();
            }
        });
    }
}

/**
 * Initialize dark mode based on preference
 */
function initDarkMode() {
    // Check for saved preference
    const savedMode = localStorage.getItem('darkMode');
    if (savedMode === 'enabled') {
        document.body.classList.add('dark-mode');
    }

    // Check system preference
    if (!savedMode && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('dark-mode');
    }
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    // Register form
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            let isValid = true;

            // Validate email
            const email = document.getElementById('email');
            if (email && !validateEmail(email.value)) {
                showFieldError(email, 'Format email tidak valid');
                isValid = false;
            } else if (email) {
                clearFieldError(email);
            }

            // Validate password
            const password = document.getElementById('password');
            const password2 = document.getElementById('password2');

            if (password) {
                const pwdResult = validatePasswordStrength(password.value);
                if (!pwdResult.valid) {
                    showFieldError(password, pwdResult.message);
                    isValid = false;
                } else {
                    clearFieldError(password);
                }
            }

            // Validate password confirmation
            if (password && password2 && password.value !== password2.value) {
                showFieldError(password2, 'Password dan konfirmasi tidak cocok');
                isValid = false;
            } else if (password2) {
                clearFieldError(password2);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Login form
    const loginForm = document.querySelector('form[action=""]');
    if (loginForm && !registerForm) {
        loginForm.addEventListener('submit', function (e) {
            let isValid = true;

            const email = document.getElementById('email');
            if (email && !validateEmail(email.value)) {
                showFieldError(email, 'Format email tidak valid');
                isValid = false;
            } else if (email) {
                clearFieldError(email);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Forgot password form
    const forgotForm = document.querySelector('form[action="forgot_password.php"]');
    if (forgotForm) {
        forgotForm.addEventListener('submit', function (e) {
            let isValid = true;

            const email = document.getElementById('email');
            if (email && !validateEmail(email.value)) {
                showFieldError(email, 'Format email tidak valid');
                isValid = false;
            } else if (email) {
                clearFieldError(email);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Reset password form
    const resetForm = document.querySelector('form[action="reset_password.php"]');
    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            let isValid = true;

            const password = document.getElementById('password');
            const password2 = document.getElementById('password2');

            if (password) {
                const pwdResult = validatePasswordStrength(password.value);
                if (!pwdResult.valid) {
                    showFieldError(password, pwdResult.message);
                    isValid = false;
                } else {
                    clearFieldError(password);
                }
            }

            if (password && password2 && password.value !== password2.value) {
                showFieldError(password2, 'Password dan konfirmasi tidak cocok');
                isValid = false;
            } else if (password2) {
                clearFieldError(password2);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Show progress indicator
 * @param {string} message - Progress message
 * @param {number} progress - Progress percentage (0-100)
 */
function showProgress(message, progress = 0) {
    // Remove existing progress
    const existingProgress = document.querySelector('.progress-indicator');
    if (existingProgress) existingProgress.remove();

    // Create progress indicator
    const progressDiv = document.createElement('div');
    progressDiv.className = 'progress-indicator';
    progressDiv.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        padding: 1.5rem 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10002;
        min-width: 300px;
        text-align: center;
    `;

    progressDiv.innerHTML = `
        <div style="margin-bottom: 1rem; color: #555; font-size: 0.9rem;">${escapeHtml(message)}</div>
        <div style="
            background: #e0e0e0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        ">
            <div style="
                background: #2980b9;
                height: 100%;
                width: ${progress}%;
                transition: width 0.3s ease;
            "></div>
        </div>
        <div style="color: #777; font-size: 0.8rem;">${progress}%</div>
    `;

    document.body.appendChild(progressDiv);

    return {
        update: (newProgress, newMessage) => {
            const bar = progressDiv.querySelector('div > div > div');
            const text = progressDiv.querySelector('div > div:last-child');
            const msg = progressDiv.querySelector('div > div:first-child');
            if (bar) bar.style.width = newProgress + '%';
            if (text) text.textContent = newProgress + '%';
            if (msg && newMessage) msg.textContent = newMessage;
        },
        close: () => {
            progressDiv.remove();
        }
    };
}

/**
 * Show button loading state
 * @param {HTMLElement} button - Button element
 * @param {string} originalText - Original button text
 * @param {boolean} isLoading - Loading state
 */
function setButtonLoading(button, originalText, isLoading = true) {
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalText = originalText;
        button.innerHTML = `
            <span style="display:inline-block;width:16px;height:16px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;margin-right:0.5rem;vertical-align:middle;"></span>
            <span style="vertical-align:middle;">Memuat...</span>
        `;
    } else {
        button.disabled = false;
        button.textContent = originalText;
    }
}

/**
 * Retry function for transient errors
 * @param {Function} fn - Function to retry
 * @param {number} maxRetries - Maximum number of retries (default: 3)
 * @param {number} delay - Delay between retries in ms (default: 1000)
 * @returns {Promise} - Promise that resolves when successful or rejects after max retries
 */
async function retry(fn, maxRetries = 3, delay = 1000) {
    let lastError;

    for (let i = 0; i < maxRetries; i++) {
        try {
            return await fn();
        } catch (error) {
            lastError = error;

            // Don't retry on non-transient errors (4xx, 5xx except 429, 503, 504)
            if (error.response) {
                const status = error.response.status;
                if (status >= 400 && status < 500 && status !== 429) {
                    throw error;
                }
            }

            // Wait before retrying (exponential backoff)
            if (i < maxRetries - 1) {
                await new Promise(resolve => setTimeout(resolve, delay * Math.pow(2, i)));
            }
        }
    }

    throw lastError;
}

/**
 * Fetch with retry for network requests
 * @param {string} url - URL to fetch
 * @param {object} options - Fetch options
 * @param {number} maxRetries - Maximum retries
 * @returns {Promise} - Fetch response
 */
async function fetchWithRetry(url, options = {}, maxRetries = 3) {
    // Show loading indicator
    const loadingIndicator = showLoading('Memuat data...');

    return retry(async () => {
        const response = await fetch(url, options);

        if (!response.ok) {
            const error = new Error(`HTTP error! status: ${response.status}`);
            error.response = response;
            throw error;
        }

        return response;
    }, maxRetries).finally(() => {
        // Hide loading indicator
        loadingIndicator.close();
    });
}

/**
 * Show loading spinner
 * @param {string} message - Loading message
 * @returns {object} - Object with close method
 */
function showLoading(message = 'Memuat...') {
    // Remove existing loading
    const existingLoading = document.querySelector('.loading-indicator');
    if (existingLoading) existingLoading.remove();

    // Create loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-indicator';
    loadingDiv.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.95);
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10003;
        text-align: center;
        min-width: 200px;
    `;

    loadingDiv.innerHTML = `
        <div style="
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2980b9;
            border-radius: 50%;
            animation: spin 0.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            margin: 0 auto 1rem auto;
        "></div>
        <div style="color: #555; font-size: 0.9rem;">${escapeHtml(message)}</div>
    `;

    // Add animation keyframes if not exists
    if (!document.querySelector('#loading-animations')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'loading-animations';
        styleSheet.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(styleSheet);
    }

    document.body.appendChild(loadingDiv);

    return {
        close: () => {
            loadingDiv.remove();
        }
    };
}

/**
 * Initialize keyboard shortcuts
 */
function initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Don't trigger shortcuts when typing in input fields
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
            return;
        }

        // Alt + H: Go to homepage
        if (e.altKey && e.key === 'h') {
            e.preventDefault();
            window.location.href = '/permen/index.php';
        }

        // Alt + L: Go to login
        if (e.altKey && e.key === 'l') {
            e.preventDefault();
            window.location.href = '/permen/pages/login.php';
        }

        // Alt + D: Go to dashboard (if logged in)
        if (e.altKey && e.key === 'd') {
            e.preventDefault();
            window.location.href = '/permen/pages/user_dashboard.php';
        }

        // Alt + T: Go to tryout
        if (e.altKey && e.key === 't') {
            e.preventDefault();
            window.location.href = '/permen/pages/tryout.php';
        }

        // Alt + M: Go to materi
        if (e.altKey && e.key === 'm') {
            e.preventDefault();
            window.location.href = '/permen/pages/materi.php?subtes=TWK';
        }

        // Alt + L: Go to latihan
        if (e.altKey && e.key === 'L') {
            e.preventDefault();
            window.location.href = '/permen/pages/latihan.php';
        }

        // Escape: Close modals/menus
        if (e.key === 'Escape') {
            // Close mobile menu
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                const hamburger = document.querySelector('.hamburger');
                if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
            }

            // Close confirmation dialog
            const dialog = document.querySelector('.confirm-dialog-overlay');
            if (dialog) dialog.remove();

            // Close toast
            const toast = document.querySelector('.toast-notification');
            if (toast) toast.remove();
        }
    });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        initFormValidation();
        initDarkMode();
        initMobileMenu();
        initKeyboardShortcuts();
        initServiceWorker();
        initPWAInstallPrompt();
        initAnalytics();
    });
} else {
    initFormValidation();
    initDarkMode();
    initMobileMenu();
    initKeyboardShortcuts();
    initServiceWorker();
    initPWAInstallPrompt();
    initAnalytics();
}

/**
 * Initialize Service Worker for PWA
 */
function initServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/permen/sw.js')
            .then(registration => {
                console.log('Service Worker registered:', registration);

                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New version available
                            showUpdateNotification();
                        }
                    });
                });
            })
            .catch(error => {
                console.error('Service Worker registration failed:', error);
            });

        // Listen for messages from service worker
        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data.type === 'SYNC_COMPLETE') {
                showToast('Jawaban offline berhasil disinkronisasi', 'success');
            }
        });
    }
}

/**
 * Initialize PWA Install Prompt
 */
let deferredPrompt;

function initPWAInstallPrompt() {
    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Show install button
        showInstallButton();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hideInstallButton();
        showToast('Aplikasi berhasil diinstall!', 'success');
    });
}

function showInstallButton() {
    let installBtn = document.getElementById('pwa-install-btn');
    if (!installBtn) {
        installBtn = document.createElement('button');
        installBtn.id = 'pwa-install-btn';
        installBtn.innerHTML = '📱 Install App';
        installBtn.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:12px 20px;background:#2980b9;color:#fff;border:none;border-radius:25px;cursor:pointer;font-size:14px;font-weight:bold;box-shadow:0 4px 12px rgba(0,0,0,0.3);z-index:9999;transition:transform 0.2s;';
        installBtn.addEventListener('click', handleInstallClick);
        document.body.appendChild(installBtn);
    }
    installBtn.style.display = 'block';
}

function hideInstallButton() {
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.style.display = 'none';
    }
}

function handleInstallClick() {
    if (!deferredPrompt) return;

    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
            console.log('User accepted the install prompt');
        }
        deferredPrompt = null;
        hideInstallButton();
    });
}

function showUpdateNotification() {
    const notification = document.createElement('div');
    notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#27ae60;color:#fff;padding:15px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.2);z-index:10000;max-width:300px;';
    notification.innerHTML = `
        <div style="font-weight:bold;margin-bottom:5px">Update Tersedia!</div>
        <div style="font-size:13px;margin-bottom:10px">Versi baru aplikasi tersedia. Refresh untuk update.</div>
        <button onclick="location.reload()" style="background:#fff;color:#27ae60;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-weight:bold">Refresh</button>
    `;
    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), 30000);
}

/**
 * IndexedDB for offline answers storage
 */
const DB_NAME = 'SKD_CAT_BKN_Offline';
const DB_VERSION = 1;
const STORE_NAME = 'offline_answers';

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                store.createIndex('soal_id', 'soal_id', { unique: false });
                store.createIndex('user_id', 'user_id', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    });
}

async function saveOfflineAnswer(answerData) {
    try {
        const db = await openDB();
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);

        const record = {
            ...answerData,
            timestamp: new Date().toISOString(),
            synced: false
        };

        await new Promise((resolve, reject) => {
            const request = store.add(record);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });

        // Register background sync
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            const registration = await navigator.serviceWorker.ready;
            await registration.sync.register('sync-offline-answers');
        }

        return true;
    } catch (e) {
        console.error('Failed to save offline answer:', e);
        return false;
    }
}

async function getOfflineAnswers() {
    try {
        const db = await openDB();
        const transaction = db.transaction([STORE_NAME], 'readonly');
        const store = transaction.objectStore(STORE_NAME);

        return await new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch (e) {
        console.error('Failed to get offline answers:', e);
        return [];
    }
}

async function removeOfflineAnswer(id) {
    try {
        const db = await openDB();
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);

        await new Promise((resolve, reject) => {
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });

        return true;
    } catch (e) {
        console.error('Failed to remove offline answer:', e);
        return false;
    }
}

// Make offline functions available globally
window.saveOfflineAnswer = saveOfflineAnswer;
window.getOfflineAnswers = getOfflineAnswers;
window.removeOfflineAnswer = removeOfflineAnswer;

/**
 * Push Notifications Management
 */
let pushSubscription = null;

async function initPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('Push notifications not supported');
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        pushSubscription = await registration.pushManager.getSubscription();

        // Check if user has subscription in database
        const response = await fetch('/permen/api/push_notifications.php?action=check_subscription');
        const data = await response.json();

        if (data.success && data.subscribed && !pushSubscription) {
            // User has subscription in DB but not in browser - might need to resubscribe
            console.log('Subscription mismatch detected');
        }

        return pushSubscription;
    } catch (e) {
        console.error('Push notification init failed:', e);
        return null;
    }
}

async function subscribeToPushNotifications() {
    try {
        const registration = await navigator.serviceWorker.ready;

        // Request permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Notification permission denied');
        }

        // Subscribe to push
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array('YOUR_VAPID_PUBLIC_KEY_HERE')
        });

        // Send subscription to server
        const response = await fetch('/permen/api/push_notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'subscribe',
                endpoint: subscription.endpoint,
                p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
            })
        });

        const data = await response.json();
        if (data.success) {
            pushSubscription = subscription;
            showToast('Notifikasi berhasil diaktifkan', 'success');
            return true;
        } else {
            throw new Error(data.error || 'Subscription failed');
        }
    } catch (e) {
        console.error('Push subscription failed:', e);
        showToast('Gagal mengaktifkan notifikasi', 'error');
        return false;
    }
}

async function unsubscribeFromPushNotifications() {
    if (!pushSubscription) return true;

    try {
        await pushSubscription.unsubscribe();

        // Remove from server
        const response = await fetch('/permen/api/push_notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'unsubscribe',
                endpoint: pushSubscription.endpoint
            })
        });

        const data = await response.json();
        if (data.success) {
            pushSubscription = null;
            showToast('Notifikasi berhasil dinonaktifkan', 'success');
            return true;
        } else {
            throw new Error(data.error || 'Unsubscribe failed');
        }
    } catch (e) {
        console.error('Push unsubscribe failed:', e);
        showToast('Gagal menonaktifkan notifikasi', 'error');
        return false;
    }
}

async function getNotificationPreferences() {
    try {
        const response = await fetch('/permen/api/push_notifications.php?action=get_preferences');
        const data = await response.json();

        if (data.success) {
            return data.preferences;
        }
        return null;
    } catch (e) {
        console.error('Failed to get notification preferences:', e);
        return null;
    }
}

async function updateNotificationPreferences(preferences) {
    try {
        const response = await fetch('/permen/api/push_notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'update_preferences',
                ...preferences
            })
        });

        const data = await response.json();
        if (data.success) {
            showToast('Preferensi notifikasi berhasil diupdate', 'success');
            return true;
        } else {
            throw new Error(data.error || 'Update failed');
        }
    } catch (e) {
        console.error('Failed to update notification preferences:', e);
        showToast('Gagal update preferensi notifikasi', 'error');
        return false;
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

// Make push notification functions available globally
window.initPushNotifications = initPushNotifications;
window.subscribeToPushNotifications = subscribeToPushNotifications;
window.unsubscribeFromPushNotifications = unsubscribeFromPushNotifications;
window.getNotificationPreferences = getNotificationPreferences;
window.updateNotificationPreferences = updateNotificationPreferences;

/**
 * Learning Analytics Tracking
 */
let analyticsSessionId = null;
let pageStartTime = null;
let currentPageUrl = null;

function initAnalytics() {
    // Only track analytics if user is logged in
    if (typeof isLoggedIn === 'undefined' || !isLoggedIn) {
        return;
    }

    analyticsSessionId = typeof session_id !== 'undefined' ? session_id : generateSessionId();
    pageStartTime = Date.now();
    currentPageUrl = window.location.pathname;

    // Track page view
    trackEvent('page_view', {
        page_url: currentPageUrl
    });

    // Track time spent on page unload
    window.addEventListener('beforeunload', () => {
        const timeSpent = Math.floor((Date.now() - pageStartTime) / 1000);
        if (timeSpent > 0) {
            trackEvent('page_view', {
                page_url: currentPageUrl,
                time_spent_seconds: timeSpent
            }, true);
        }
    });

    // Track visibility changes (user switching tabs)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            const timeSpent = Math.floor((Date.now() - pageStartTime) / 1000);
            if (timeSpent > 0) {
                trackEvent('page_view', {
                    page_url: currentPageUrl,
                    time_spent_seconds: timeSpent
                }, true);
            }
        } else {
            pageStartTime = Date.now();
        }
    });
}

function generateSessionId() {
    return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function trackEvent(eventType, data = {}, sendBeacon = false) {
    // Only track analytics if user is logged in
    if (typeof isLoggedIn === 'undefined' || !isLoggedIn) {
        return;
    }

    const payload = {
        action: 'track_event',
        event_type: eventType,
        session_id: analyticsSessionId,
        ...data
    };

    if (sendBeacon && navigator.sendBeacon) {
        const formData = new URLSearchParams(payload);
        navigator.sendBeacon('/permen/api/learning_analytics.php', formData);
    } else {
        fetch('/permen/api/learning_analytics.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        }).catch(e => console.error('Analytics tracking failed:', e));
    }
}

function trackMateriAccess(materiId, subtes = null, topik = null) {
    trackEvent('materi_access', {
        materi_id: materiId,
        subtes: subtes,
        topik: topik
    });
}

function trackSoalView(soalId, subtes = null, topik = null) {
    trackEvent('soal_view', {
        soal_id: soalId,
        subtes: subtes,
        topik: topik
    });
}

function trackSoalAnswer(soalId, subtes = null, topik = null) {
    trackEvent('soal_answer', {
        soal_id: soalId,
        subtes: subtes,
        topik: topik
    });
}

function trackQuizStart(subtes = null) {
    trackEvent('quiz_start', {
        subtes: subtes
    });
}

function trackQuizComplete(subtes = null, score = null) {
    trackEvent('quiz_complete', {
        subtes: subtes,
        time_spent_seconds: Math.floor((Date.now() - pageStartTime) / 1000)
    });
}

function trackTryoutStart(tryoutId = null) {
    trackEvent('tryout_start', {
        page_url: currentPageUrl,
        data: JSON.stringify({ tryout_id: tryoutId })
    });
}

function trackTryoutComplete(tryoutId = null, score = null) {
    trackEvent('tryout_complete', {
        page_url: currentPageUrl,
        time_spent_seconds: Math.floor((Date.now() - pageStartTime) / 1000),
        data: JSON.stringify({ tryout_id: tryoutId, score: score })
    });
}

async function getLearningInsights() {
    try {
        const response = await fetch('/permen/api/learning_analytics.php?action=get_learning_insights');
        const data = await response.json();

        if (data.success) {
            return data.insights;
        }
        return [];
    } catch (e) {
        console.error('Failed to get learning insights:', e);
        return [];
    }
}

async function markInsightRead(insightId) {
    try {
        await fetch(`/permen/api/learning_analytics.php?action=mark_insight_read&insight_id=${insightId}`);
    } catch (e) {
        console.error('Failed to mark insight as read:', e);
    }
}

async function getLearningStats() {
    try {
        const response = await fetch('/permen/api/learning_analytics.php?action=get_learning_stats');
        const data = await response.json();

        if (data.success) {
            return data;
        }
        return null;
    } catch (e) {
        console.error('Failed to get learning stats:', e);
        return null;
    }
}

// Make analytics functions available globally
window.trackEvent = trackEvent;
window.trackMateriAccess = trackMateriAccess;
window.trackSoalView = trackSoalView;
window.trackSoalAnswer = trackSoalAnswer;
window.trackQuizStart = trackQuizStart;
window.trackQuizComplete = trackQuizComplete;
window.trackTryoutStart = trackTryoutStart;
window.trackTryoutComplete = trackTryoutComplete;
window.getLearningInsights = getLearningInsights;
window.markInsightRead = markInsightRead;
window.getLearningStats = getLearningStats;

