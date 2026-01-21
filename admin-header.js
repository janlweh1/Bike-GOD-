// Admin header role display
// Fetch admin profile and show role (e.g., Super Admin) in the header tab

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('get_admin_settings.php', { credentials: 'include' });
        const data = await res.json();
        if (!data || data.success !== true || !data.admin) return;
        const role = (data.admin.role || '').trim();
        const name = (data.admin.full_name || '').trim();
        const label = (name && role) ? `${name} • ${role}` : (name || role || 'Admin');
        // Prefer explicit header span within .user-profile; fallback to #headerUserName
        const profileEl = document.querySelector('.header-right .user-profile');
        const imgEl = (profileEl && profileEl.querySelector('img')) || document.getElementById('headerProfileImage');
        const el = (profileEl && profileEl.querySelector('span')) || document.getElementById('headerUserName');
        if (el) {
            el.textContent = label;
        }

        // Prefer actual photo if available; else show initials badge
        const photoUrl = (data.admin.photo_url || '').trim();
        if (imgEl && photoUrl) {
            imgEl.src = photoUrl;
            imgEl.style.display = 'inline-block';
            // Remove existing initials badge if any
            const existing = profileEl ? profileEl.querySelector('.user-initials-badge') : null;
            if (existing) existing.remove();
        } else if (profileEl && name) {
            const initials = getInitials(name);
            let badge = profileEl.querySelector('.user-initials-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'user-initials-badge';
                badge.style.display = 'inline-flex';
                badge.style.alignItems = 'center';
                badge.style.justifyContent = 'center';
                badge.style.width = '28px';
                badge.style.height = '28px';
                badge.style.borderRadius = '50%';
                badge.style.background = '#22d3ee';
                badge.style.color = '#ffffff';
                badge.style.fontSize = '12px';
                badge.style.fontWeight = '600';
                badge.style.marginRight = '8px';
                // Place before the name label
                const img = imgEl;
                if (img) { img.style.display = 'none'; }
                profileEl.insertBefore(badge, el);
            }
            badge.textContent = initials;
        }
    } catch (e) {
        // silent failure: keep default label
    }
});

function getInitials(fullName) {
    const parts = String(fullName).trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'A';
    const first = parts[0].charAt(0).toUpperCase();
    const last = (parts.length > 1 ? parts[parts.length - 1].charAt(0) : '').toUpperCase();
    return (first + last) || first;
}

// Lightweight in-app notifications initializer for all admin pages
(function () {
    if (typeof window === 'undefined') return;
    // Skip if panel already exists (dashboard.js may have set it up)
    if (document.getElementById('notif-panel')) return;

    let notifLastRentalId = null;
    let notifLastPaymentId = null;
    let notifQueue = [];
    let isAdminSession = null;

    function initNotificationsUI() {
        const btn = document.querySelector('.notification-btn');
        if (!btn) return false;

        // Badge
        const badge = document.createElement('span');
        badge.className = 'notif-badge';
        badge.style.position = 'absolute';
        badge.style.top = '6px';
        badge.style.right = '6px';
        badge.style.background = '#ef4444';
        badge.style.color = '#fff';
        badge.style.fontSize = '10px';
        badge.style.lineHeight = '14px';
        badge.style.padding = '0 5px';
        badge.style.borderRadius = '8px';
        badge.style.display = 'none';
        badge.style.minWidth = '16px';
        badge.style.textAlign = 'center';
        btn.style.position = 'relative';
        btn.appendChild(badge);

        // Dropdown panel
        const panel = document.createElement('div');
        panel.id = 'notif-panel';
        panel.style.position = 'absolute';
        panel.style.top = '48px';
        panel.style.right = '16px';
        panel.style.width = '320px';
        panel.style.maxHeight = '360px';
        panel.style.overflowY = 'auto';
        panel.style.background = '#fff';
        panel.style.border = '1px solid #e5e7eb';
        panel.style.boxShadow = '0 8px 24px rgba(0,0,0,0.12)';
        panel.style.borderRadius = '10px';
        panel.style.display = 'none';
        panel.style.zIndex = '1000';
        panel.innerHTML = '<div style="padding:10px 12px;border-bottom:1px solid #eee;font-weight:600;">Notifications</div><div id="notif-items"></div>';
        document.body.appendChild(panel);

        // Toggle panel
        btn.addEventListener('click', () => {
            const visible = panel.style.display === 'block';
            panel.style.display = visible ? 'none' : 'block';
            if (!visible) {
                const b = btn.querySelector('.notif-badge');
                if (b) b.style.display = 'none';
            }
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!panel.contains(e.target) && !btn.contains(e.target)) {
                panel.style.display = 'none';
            }
        });

        return true;
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
    }

    function pushNotification(text) {
        notifQueue.unshift({ text, time: new Date().toLocaleString() });
        notifQueue = notifQueue.slice(0, 20);
        const items = document.getElementById('notif-items');
        const badge = document.querySelector('.notification-btn .notif-badge');
        if (items) {
            items.innerHTML = notifQueue.map(n => `
                <div style="padding:10px 12px;border-bottom:1px solid #f1f5f9;display:flex;gap:10px;align-items:flex-start;">
                    <div style="width:8px;height:8px;border-radius:50%;background:#22c55e;margin-top:6px;"></div>
                    <div>
                        <div style="font-size:13px;color:#111827;">${escapeHtml(n.text)}</div>
                        <div style="font-size:11px;color:#6b7280;margin-top:4px;">${escapeHtml(n.time)}</div>
                    </div>
                </div>
            `).join('');
        }
        if (badge) {
            badge.textContent = String((parseInt(badge.textContent || '0', 10) || 0) + 1);
            badge.style.display = 'inline-block';
        }
    }

    async function checkNotifications() {
        try {
            // Ensure admin session before polling secured endpoints
            if (isAdminSession !== true) {
                try {
                    const s = await fetch('check_session.php', { credentials: 'include', cache: 'no-store' });
                    const sj = await s.json();
                    isAdminSession = !!(sj && sj.loggedIn === true && sj.userType === 'admin');
                } catch {}
                if (isAdminSession !== true) {
                    // Show a subtle hint only once
                    if (!document.getElementById('notif-items') || document.getElementById('notif-items').dataset.hintShown !== '1') {
                        const items = document.getElementById('notif-items');
                        if (items) {
                            items.dataset.hintShown = '1';
                            items.innerHTML = '<div style="padding:10px 12px;color:#6b7280;">Sign in as admin to receive live notifications.</div>';
                        }
                    }
                    return; // skip until admin session is present
                }
            }
            // Rentals
            const rRes = await fetch('get_rentals.php?ts=' + Date.now(), { credentials: 'include', cache: 'no-store' });
            if (!rRes.ok) return;
            const rData = await rRes.json();
            if (rData && rData.success && Array.isArray(rData.rentals)) {
                const maxId = rData.rentals.reduce((m, r) => Math.max(m, parseInt(r.id || '0', 10) || 0), 0);
                if (notifLastRentalId === null) {
                    // Clamp stored last id to current max range
                    notifLastRentalId = maxId; // baseline
                } else if (maxId > notifLastRentalId) {
                    const newRentals = rData.rentals.filter(r => (parseInt(r.id || '0', 10) || 0) > notifLastRentalId);
                    const newCount = newRentals.length;
                    // Compose a concise message; include first bike/customer if available
                    if (newCount === 1) {
                        const one = newRentals[0];
                        const who = (one && one.customerName) ? ` by ${one.customerName}` : '';
                        const what = (one && one.name) ? `: ${one.name}` : '';
                        pushNotification(`New rental${who}${what}`);
                    } else {
                        pushNotification(`${newCount} new rentals created`);
                    }
                    notifLastRentalId = maxId;
                } else if (maxId < notifLastRentalId) {
                    // DB reset or different environment; re-baseline to avoid permanent suppression
                    notifLastRentalId = maxId;
                }
            }

            // Payments
            const pRes = await fetch('get_payments.php?ts=' + Date.now(), { credentials: 'include', cache: 'no-store' });
            if (!pRes.ok) return;
            const pData = await pRes.json();
            if (pData && pData.success && Array.isArray(pData.payments)) {
                const maxPid = pData.payments.reduce((m, p) => Math.max(m, parseInt(p.id || '0', 10) || 0), 0);
                if (notifLastPaymentId === null) {
                    notifLastPaymentId = maxPid; // baseline
                } else if (maxPid > notifLastPaymentId) {
                    const newPayments = pData.payments.filter(p => (parseInt(p.id || '0', 10) || 0) > notifLastPaymentId);
                    pushNotification(`${newPayments.length} new payment${newPayments.length>1?'s':''} received`);
                    notifLastPaymentId = maxPid;
                }
            }
        } catch (e) {
            // silent
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        let attempts = 0;
        const maxAttempts = 30; // ~15s total with 500ms interval
        const timer = setInterval(() => {
            attempts += 1;
            const ok = initNotificationsUI();
            if (ok) {
                clearInterval(timer);
                // Load last seen id from storage
                try {
                    const v = localStorage.getItem('notifLastRentalId');
                    if (v) notifLastRentalId = parseInt(v, 10) || null;
                } catch {}
                checkNotifications();
                setInterval(() => {
                    checkNotifications().then(() => {
                        try { if (notifLastRentalId != null) localStorage.setItem('notifLastRentalId', String(notifLastRentalId)); } catch {}
                    });
                }, 5000);
            } else if (attempts >= maxAttempts) {
                clearInterval(timer);
            }
        }, 500);
    });
})();
