/**
 * Common JS — SKD CAT-BKN
 * Fungsi reusable vanilla JS untuk seluruh aplikasi
 */

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
    
    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.8rem';
    errorDiv.style.marginTop = '0.3rem';
    errorDiv.textContent = message;
    field.parentElement.appendChild(errorDiv);
}

/**
 * Clear error message for form field
 * @param {HTMLElement} field - The input field
 */
function clearFieldError(field) {
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) existingError.remove();
    field.style.borderColor = '#ddd';
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
        registerForm.addEventListener('submit', function(e) {
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
        loginForm.addEventListener('submit', function(e) {
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
        forgotForm.addEventListener('submit', function(e) {
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
        resetForm.addEventListener('submit', function(e) {
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

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initFormValidation();
        initDarkMode();
    });
} else {
    initFormValidation();
    initDarkMode();
}
