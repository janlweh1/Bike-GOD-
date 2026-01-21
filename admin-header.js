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
