(()=>{var r=typeof BASE_URL<"u"?BASE_URL:"";var a=localStorage.getItem("theme");a&&document.documentElement.setAttribute("data-theme",a);var c=!1;async function s(){try{let e=await fetch(r+"/api/get_notifications.php?limit=10");if(!e.ok)return;let t=await e.json();t.success&&t.data&&(l(t.data.notifications),m(t.data.unread_count))}catch{}}function l(e){let t=document.getElementById("notifList");if(!e||e.length===0){t.innerHTML='<p style="color:#777;font-size:.85rem;text-align:center;padding:1rem">Tidak ada notifikasi</p>';return}let o={info:"#2980b9",success:"#27ae60",warning:"#f39c12",error:"#e74c3c"},i="";e.forEach(n=>{let d=n.is_read?"#f8f9fa":"#eaf2f8";i+=`
        <div style="background:${d};padding:.8rem;border-bottom:1px solid #eee;cursor:pointer" onclick="openNotification(${n.id}, '${n.link||""}')" class="notif-item" data-id="${n.id}">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
                <span style="width:8px;height:8px;border-radius:50%;background:${o[n.type]||"#999"}"></span>
                <span style="font-weight:600;font-size:.9rem;color:#333">${n.title}</span>
            </div>
            <p style="margin:0;font-size:.85rem;color:#555;line-height:1.4">${n.message}</p>
            <p style="margin:.3rem 0 0;font-size:.75rem;color:#999">${n.created_at}</p>
        </div>`}),t.innerHTML=i}function m(e){let t=document.getElementById("notifBadge");t&&(t.textContent=e>0?e:"",t.style.display=e>0?"inline-block":"none")}document.addEventListener("click",e=>{let t=document.getElementById("notifDropdown");if(!t)return;!e.target.closest('button[onclick="toggleNotifications()"]')&&c&&!t.contains(e.target)&&(c=!1,t.style.display="none")});document.addEventListener("DOMContentLoaded",function(){s()});})();
