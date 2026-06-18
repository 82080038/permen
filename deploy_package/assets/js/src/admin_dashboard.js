/**
 * Admin Dashboard JavaScript
 * Handles admin dashboard functionality including tabs, data loading, and theme toggle
 */

// Theme toggle
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}

// Load saved theme
const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
    document.documentElement.setAttribute('data-theme', savedTheme);
}

// Tab switching
function showTab(id) {
    ['analytics','feedback','moderation','revision','users','tryouts','events','soal','materi','tips','media','reports','generator','config'].forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const tab = document.getElementById('tab-' + t);
        if (panel) panel.style.display = 'none';
        if (tab) tab.classList.remove('active');
    });
    
    const activePanel = document.getElementById('panel-' + id);
    const activeTab = document.getElementById('tab-' + id);
    if (activePanel) activePanel.style.display = 'block';
    if (activeTab) activeTab.classList.add('active');
    
    // Load data based on tab
    if (id === 'soal') {
        if (typeof loadSoalList === 'function') loadSoalList();
        if (typeof loadTags === 'function') loadTags();
    }
    if (id === 'materi' && typeof loadMateriList === 'function') loadMateriList();
    if (id === 'tips' && typeof loadTipsList === 'function') loadTipsList();
    if (id === 'media' && typeof loadMediaLibrary === 'function') loadMediaLibrary();
    if (id === 'revision' && typeof loadRevisionQueue === 'function') loadRevisionQueue();
    if (id === 'reports' && typeof loadReports === 'function') loadReports();
    if (id === 'feedback' && typeof loadFeedback === 'function') loadFeedback();
    if (id === 'events' && typeof loadEvents === 'function') loadEvents();
    if (id === 'moderation' && typeof loadModerationQueue === 'function') loadModerationQueue();
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Show default tab if no tab is active
    const defaultTab = 'analytics';
    if (!document.querySelector('.tab.active')) {
        showTab(defaultTab);
    }
});
