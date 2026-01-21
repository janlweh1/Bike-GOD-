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
        const el = document.querySelector('.header-right .user-profile span') || document.getElementById('headerUserName');
        if (el) {
            el.textContent = label;
        }

        // Show initials badge next to the header label
        const userProfile = document.querySelector('.header-right .user-profile');
        if (userProfile && name) {
            const initials = getInitials(name);
            let badge = userProfile.querySelector('.user-initials-badge');
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
                const img = userProfile.querySelector('img');
                if (img) { img.style.display = 'none'; }
                userProfile.insertBefore(badge, el);
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
