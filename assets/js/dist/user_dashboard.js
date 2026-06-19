(() => {
  // assets/js/src/user_dashboard.js
  var _baseUrl = typeof BASE_URL !== "undefined" ? BASE_URL : "";
  var savedTheme = localStorage.getItem("theme");
  if (savedTheme) {
    document.documentElement.setAttribute("data-theme", savedTheme);
  }
  var notifDropdownOpen = false;
  async function loadNotifications() {
    try {
      const res = await fetch(_baseUrl + "/api/get_notifications.php?limit=10");
      if (!res.ok) {
        return;
      }
      const data = await res.json();
      if (data.success && data.data) {
        renderNotifications(data.data.notifications);
        updateNotifBadge(data.data.unread_count);
      }
    } catch (e) {
    }
  }
  function renderNotifications(notifications) {
    const list = document.getElementById("notifList");
    if (!notifications || notifications.length === 0) {
      list.innerHTML = '<p style="color:#777;font-size:.85rem;text-align:center;padding:1rem">Tidak ada notifikasi</p>';
      return;
    }
    const typeColors = {
      "info": "#2980b9",
      "success": "#27ae60",
      "warning": "#f39c12",
      "error": "#e74c3c"
    };
    let html = "";
    notifications.forEach((n) => {
      const bgColor = n.is_read ? "#f8f9fa" : "#eaf2f8";
      html += `
        <div style="background:${bgColor};padding:.8rem;border-bottom:1px solid #eee;cursor:pointer" onclick="openNotification(${n.id}, '${n.link || ""}')" class="notif-item" data-id="${n.id}">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
                <span style="width:8px;height:8px;border-radius:50%;background:${typeColors[n.type] || "#999"}"></span>
                <span style="font-weight:600;font-size:.9rem;color:#333">${n.title}</span>
            </div>
            <p style="margin:0;font-size:.85rem;color:#555;line-height:1.4">${n.message}</p>
            <p style="margin:.3rem 0 0;font-size:.75rem;color:#999">${n.created_at}</p>
        </div>`;
    });
    list.innerHTML = html;
  }
  function updateNotifBadge(count) {
    const badge = document.getElementById("notifBadge");
    if (badge) {
      badge.textContent = count > 0 ? count : "";
      badge.style.display = count > 0 ? "inline-block" : "none";
    }
  }
  document.addEventListener("click", (e) => {
    const dropdown = document.getElementById("notifDropdown");
    if (!dropdown) return;
    const button = e.target.closest('button[onclick="toggleNotifications()"]');
    if (!button && notifDropdownOpen && !dropdown.contains(e.target)) {
      notifDropdownOpen = false;
      dropdown.style.display = "none";
    }
  });
  document.addEventListener("DOMContentLoaded", function() {
    loadNotifications();
  });
})();
