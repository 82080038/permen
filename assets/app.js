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
