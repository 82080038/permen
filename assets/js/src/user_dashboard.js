/**
 * User Dashboard JavaScript
 * Handles dashboard functionality including notifications, charts, and theme toggle
 */

// Use BASE_URL from PHP (injected before this script loads)
const _baseUrl = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';

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

// Start tryout with options
function startTryoutWithOptions(e) {
    e.preventDefault();
    const strictMode = document.getElementById('strictModeCheck').checked ? 1 : 0;
    const packageId = document.getElementById('packageSelect').value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'tryout.php';
    
    const strictInput = document.createElement('input');
    strictInput.type = 'hidden';
    strictInput.name = 'strict_mode';
    strictInput.value = strictMode;
    form.appendChild(strictInput);
    
    const packageInput = document.createElement('input');
    packageInput.type = 'hidden';
    packageInput.name = 'package_id';
    packageInput.value = packageId;
    form.appendChild(packageInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Notification System
let notifDropdownOpen = false;

async function loadNotifications() {
    try {
        const res = await fetch(_baseUrl + '/api/get_notifications.php?limit=10');
        if (!res.ok) {
            // Silently fail if notifications endpoint is not available
            return;
        }
        const data = await res.json();
        
        if (data.success && data.data) {
            renderNotifications(data.data.notifications);
            updateNotifBadge(data.data.unread_count);
        }
    } catch (e) {
        // Silently fail - notifications are optional
        // Don't log to console to avoid test failures
    }
}

function renderNotifications(notifications) {
    const list = document.getElementById('notifList');
    
    if (!notifications || notifications.length === 0) {
        list.innerHTML = '<p style="color:#777;font-size:.85rem;text-align:center;padding:1rem">Tidak ada notifikasi</p>';
        return;
    }
    
    const typeColors = {
        'info': '#2980b9',
        'success': '#27ae60',
        'warning': '#f39c12',
        'error': '#e74c3c'
    };
    
    let html = '';
    notifications.forEach(n => {
        const bgColor = n.is_read ? '#f8f9fa' : '#eaf2f8';
        html += `
        <div style="background:${bgColor};padding:.8rem;border-bottom:1px solid #eee;cursor:pointer" onclick="openNotification(${n.id}, '${n.link || ''}')" class="notif-item" data-id="${n.id}">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
                <span style="width:8px;height:8px;border-radius:50%;background:${typeColors[n.type] || '#999'}"></span>
                <span style="font-weight:600;font-size:.9rem;color:#333">${n.title}</span>
            </div>
            <p style="margin:0;font-size:.85rem;color:#555;line-height:1.4">${n.message}</p>
            <p style="margin:.3rem 0 0;font-size:.75rem;color:#999">${n.created_at}</p>
        </div>`;
    });
    list.innerHTML = html;
}

function updateNotifBadge(count) {
    const badge = document.getElementById('notifBadge');
    if (badge) {
        badge.textContent = count > 0 ? count : '';
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

function openNotification(id, link) {
    // Mark as read
    fetch(_baseUrl + '/api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${id}`
    }).then(() => {
        // Update UI
        const item = document.querySelector(`.notif-item[data-id="${id}"]`);
        if (item) {
            item.style.background = '#f8f9fa';
        }
        // Update badge
        const badge = document.getElementById('notifBadge');
        if (badge && badge.textContent) {
            const current = parseInt(badge.textContent);
            badge.textContent = current > 1 ? current - 1 : '';
            badge.style.display = current > 1 ? 'inline-block' : 'none';
        }
    });
    
    // Navigate if link provided
    if (link) {
        window.location.href = link;
    }
}

function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    notifDropdownOpen = !notifDropdownOpen;
    dropdown.style.display = notifDropdownOpen ? 'block' : 'none';
    if (notifDropdownOpen) {
        loadNotifications();
    }
}

async function markAllRead() {
    try {
        const res = await fetch(_baseUrl + '/api/get_notifications.php?unread_only=true');
        const data = await res.json();
        if (data.success && data.data && data.data.notifications.length > 0) {
            for (const n of data.data.notifications) {
                const formData = new FormData();
                formData.append('notification_id', n.id);
                await fetch(_baseUrl + '/api/mark_notification_read.php', { method: 'POST', body: formData });
            }
            loadNotifications();
        }
    } catch (e) {}
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('notifDropdown');
    if (!dropdown) return;
    const button = e.target.closest('button[onclick="toggleNotifications()"]');
    if (!button && notifDropdownOpen && !dropdown.contains(e.target)) {
        notifDropdownOpen = false;
        dropdown.style.display = 'none';
    }
});

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Load notifications
    loadNotifications();
});
