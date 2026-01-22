// Dashboard Data Management
let allBikes = [];
let activeRentals = [];
let rentalHistory = [];
let rentalsSummary = null;
let paymentsSummary = null;

// Initialize dashboard
function initDashboard() {
    Promise.all([loadBikesData(), loadRentalData(), loadCustomersSummary(), loadPaymentsSummary()])
        .then(() => {
            updateStatistics();
            displayRecentRentals();
            displayAvailableBikes();
        })
        .catch(() => {
            updateStatistics();
            displayRecentRentals();
            displayAvailableBikes();
        });
}

// Load bikes from shared-bikes-data.js
function loadBikesData() {
    // Prefer server endpoint; fallback to shared-bikes-data.js if present
    return fetch('get_bikes.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.bikes)) {
                allBikes = data.bikes.map(b => ({
                    id: b.id,
                    name: b.model,
                    category: (b.type || 'City Bike').toLowerCase().includes('mountain') ? 'mountain'
                              : (b.type || '').toLowerCase().includes('road') ? 'road'
                              : (b.type || '').toLowerCase().includes('electric') ? 'electric'
                              : (b.type || '').toLowerCase().includes('kid') ? 'kids'
                              : (b.type || '').toLowerCase().includes('premium') ? 'premium'
                              : 'city',
                    condition: (b.condition || 'Excellent').toLowerCase(),
                    status: (b.availability || 'Available').toLowerCase() === 'available' ? 'available' : 'unavailable',
                    price: b.hourly_rate || 0
                }));
            } else {
                throw new Error('fallback');
            }
        })
        .catch(() => {
            if (typeof getBikesArray === 'function') {
                allBikes = getBikesArray();
            } else {
                allBikes = [];
            }
        });
}

// Load rental data from localStorage
function loadRentalData() {
    // Prefer server rentals endpoint; fallback to localStorage
    return fetch('get_rentals.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (!data || data.success === false) throw new Error('fallback');
            const rentals = Array.isArray(data.rentals) ? data.rentals : [];
            rentalsSummary = data.summary || null;
            activeRentals = rentals.filter(r => r.status === 'active');
            rentalHistory = rentals.filter(r => r.status === 'completed');
        })
        .catch(() => {
            try {
                const storedActiveRentals = localStorage.getItem('activeRentals');
                const storedHistory = localStorage.getItem('rentalHistory');
                activeRentals = storedActiveRentals ? JSON.parse(storedActiveRentals) : [];
                rentalHistory = storedHistory ? JSON.parse(storedHistory) : [];
                rentalsSummary = null;
            } catch (error) {
                activeRentals = [];
                rentalHistory = [];
                rentalsSummary = null;
            }
        });
}

function loadCustomersSummary() {
    // Load total customers and new this week from server
    return fetch('get_customers.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (!data || data.success === false) throw new Error('failed');
            window.__customersSummary = data.summary || null;
        })
        .catch(() => { window.__customersSummary = null; });
}

// Load payments summary for revenue analytics
function loadPaymentsSummary() {
    return fetch('get_payments.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (!data || data.success === false) throw new Error('failed');
            paymentsSummary = data.summary || null;
        })
        .catch(() => { paymentsSummary = null; });
}

// Update all statistics
function updateStatistics() {
    updateTotalBikes();
    updateActiveRentals();
    updateTotalCustomers();
    updateRevenueToday();
}

// Update total bikes count
function updateTotalBikes() {
    const total = allBikes.length;
    document.getElementById('totalBikes').textContent = total;
    // No server-provided delta; display available count as a hint
    const available = allBikes.filter(b => b.status === 'available').length;
    document.getElementById('bikesChange').textContent = `${available} available`;
}

// Update active rentals count
function updateActiveRentals() {
    const activeCount = rentalsSummary && typeof rentalsSummary.active === 'number'
        ? rentalsSummary.active : activeRentals.length;
    const todayRentals = calculateTodayRentals();
    document.getElementById('activeRentals').textContent = activeCount;
    document.getElementById('rentalsChange').textContent = `+${todayRentals} today`;
}

// Calculate rentals started today
function calculateTodayRentals() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let count = 0;
    
    // Check active rentals
    activeRentals.forEach(rental => {
        const startDate = new Date(rental.startTime);
        startDate.setHours(0, 0, 0, 0);
        if (startDate.getTime() === today.getTime()) {
            count++;
        }
    });
    
    // Check rental history
    rentalHistory.forEach(rental => {
        const startDate = new Date(rental.startTime);
        startDate.setHours(0, 0, 0, 0);
        if (startDate.getTime() === today.getTime()) {
            count++;
        }
    });
    
    return count;
}

// Update total customers (unique customers from rentals)
function updateTotalCustomers() {
    if (window.__customersSummary) {
        const total = window.__customersSummary.totalMembers || 0;
        const thisWeek = window.__customersSummary.newThisWeek || 0;
        document.getElementById('totalCustomers').textContent = total;
        document.getElementById('customersChange').textContent = `+${thisWeek} this week`;
        return;
    }
    // Fallback to unique customers from rentals
    const uniqueCustomers = new Set();
    activeRentals.forEach(r => { if (r.customerName) uniqueCustomers.add(r.customerName); });
    rentalHistory.forEach(r => { if (r.customerName) uniqueCustomers.add(r.customerName); });
    const total = uniqueCustomers.size || (activeRentals.length + rentalHistory.length);
    document.getElementById('totalCustomers').textContent = total;
    document.getElementById('customersChange').textContent = `+0 this week`;
}

// Update revenue today
function updateRevenueToday() {
    // Prefer payments-based revenue so it matches the Payments page
    if (paymentsSummary && typeof paymentsSummary.todayRevenue === 'number') {
        const revenue = paymentsSummary.todayRevenue;
        document.getElementById('revenueToday').textContent = `₱${Math.round(revenue)}`;
        document.getElementById('revenueChange').textContent = revenue > 0 ? '+100%' : '+0%';
        return;
    }
    // Fallback: use rentals summary if available
    if (rentalsSummary && typeof rentalsSummary.todayRevenue === 'number') {
        const revenue = rentalsSummary.todayRevenue;
        document.getElementById('revenueToday').textContent = `₱${Math.round(revenue)}`;
        document.getElementById('revenueChange').textContent = revenue > 0 ? '+100%' : '+0%';
        return;
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let revenue = 0;
    rentalHistory.forEach(rental => {
        if (rental.endTime) {
            const endDate = new Date(rental.endTime);
            endDate.setHours(0, 0, 0, 0);
            if (endDate.getTime() === today.getTime()) {
                revenue += rental.cost || 0;
            }
        }
    });
    activeRentals.forEach(rental => {
        const startDate = new Date(rental.startTime);
        startDate.setHours(0, 0, 0, 0);
        if (startDate.getTime() === today.getTime()) {
            revenue += rental.cost || 0;
        }
    });
    document.getElementById('revenueToday').textContent = `₱${Math.round(revenue)}`;
    document.getElementById('revenueChange').textContent = revenue > 0 ? '+100%' : '+0%';
}

// Display recent rentals in table
function displayRecentRentals() {
    const tbody = document.getElementById('recentRentalsTable');
    // From server data if available
    let all = [];
    activeRentals.forEach((r, i) => all.push({ ...r, rentalId: r.id || `A${i}` }));
    rentalHistory.forEach((r, i) => all.push({ ...r, rentalId: r.id || `C${i}` }));
    all.sort((a,b) => new Date(b.startTime || 0) - new Date(a.startTime || 0));
    if (all.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 2rem; color: #7f8c8d;">
                    No rentals yet
                </td>
            </tr>
        `;
        return;
    }
    tbody.innerHTML = all.slice(0,5).map(rental => {
        // Prefer the actual pickup date from backend/local data; fallback to startTime
        const baseStart = rental.pickupDate || rental.startTime || new Date();
        const startDate = formatDate(baseStart);

        // Use the returnDate field coming directly from the rentals API
        // (which is based on DB return_date / Returns table) when present;
        // otherwise, fall back to actual endTime or the start date.
        const baseEnd = rental.returnDate || rental.endTime || baseStart;
        const returnDate = formatDate(baseEnd);
        const statusClass = rental.status === 'active' ? 'active' : rental.status === 'completed' ? 'completed' : 'overdue';
        const statusText = statusClass.charAt(0).toUpperCase() + statusClass.slice(1);
        return `
            <tr>
                <td>#${rental.rentalId}</td>
                <td>${rental.customerName || 'Customer'}</td>
                <td>${rental.name}</td>
                <td>${startDate}</td>
                <td>${returnDate}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }).join('');
}

// Display available bikes in table
function displayAvailableBikes() {
    const tbody = document.getElementById('availableBikesTable');
    
    const availableBikes = allBikes.filter(bike => bike.status === 'available').slice(0, 5);
    
    if (availableBikes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 2rem; color: #7f8c8d;">
                    No bikes available
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = availableBikes.map(bike => {
        const conditionClass = bike.condition === 'excellent' ? 'excellent' : 'good';
        const conditionText = bike.condition.charAt(0).toUpperCase() + bike.condition.slice(1);
        
        return `
            <tr>
                <td>#${bike.id}</td>
                <td>${bike.name}</td>
                <td>${bike.category.charAt(0).toUpperCase() + bike.category.slice(1)}</td>
                <td><span class="condition ${conditionClass}">${conditionText}</span></td>
                <td>₱${bike.price}</td>
            </tr>
        `;
    }).join('');
}

// Format date to readable string
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}

// Simple HTML escaping to prevent injection in dynamic content
function escapeHtml(str) {
    return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
}

// Refresh dashboard data (call this periodically or on page visibility)
function refreshDashboard() {
    Promise.all([loadBikesData(), loadRentalData(), loadCustomersSummary(), loadPaymentsSummary()])
        .then(() => {
            updateStatistics();
            displayRecentRentals();
            displayAvailableBikes();
        })
        .catch(() => {
            updateStatistics();
            displayRecentRentals();
            displayAvailableBikes();
        });
}

// Auto-refresh every 30 seconds
setInterval(refreshDashboard, 30000);

// Refresh when page becomes visible
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        refreshDashboard();
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', initDashboard);

// --- In-app Notifications (new rentals/payments) ---
let notifLastRentalId = null;
let notifLastPaymentId = null;
let notifQueue = [];

function initNotificationsUI() {
    const btn = document.querySelector('.notification-btn');
    if (!btn) return;

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
            // mark as read
            badge.style.display = 'none';
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && !btn.contains(e.target)) {
            panel.style.display = 'none';
        }
    });
}

function pushNotification(text, meta) {
    notifQueue.unshift({ text, time: new Date().toLocaleString(), meta });
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
        // Rentals
        const rRes = await fetch('get_rentals.php', { credentials: 'include' });
        const rData = await rRes.json();
        if (rData && rData.success && Array.isArray(rData.rentals)) {
            const maxId = rData.rentals.reduce((m, r) => Math.max(m, parseInt(r.id || '0', 10) || 0), 0);
            if (notifLastRentalId === null) {
                notifLastRentalId = maxId; // baseline
            } else if (maxId > notifLastRentalId) {
                const newCount = rData.rentals.filter(r => (parseInt(r.id || '0', 10) || 0) > notifLastRentalId).length;
                pushNotification(`${newCount} new rental${newCount>1?'s':''} created`, { type: 'rental', count: newCount });
                notifLastRentalId = maxId;
            }
        }

        // Payments
        const pRes = await fetch('get_payments.php', { credentials: 'include' });
        const pData = await pRes.json();
        if (pData && pData.success && Array.isArray(pData.payments)) {
            const maxPid = pData.payments.reduce((m, p) => Math.max(m, parseInt(p.id || '0', 10) || 0), 0);
            if (notifLastPaymentId === null) {
                notifLastPaymentId = maxPid; // baseline
            } else if (maxPid > notifLastPaymentId) {
                const newPayments = pData.payments.filter(p => (parseInt(p.id || '0', 10) || 0) > notifLastPaymentId);
                const totalAmt = newPayments.reduce((s, p) => s + (p.amount || 0), 0);
                pushNotification(`${newPayments.length} new payment${newPayments.length>1?'s':''} received (₱${Math.round(totalAmt)})`, { type: 'payment', count: newPayments.length, amount: totalAmt });
                notifLastPaymentId = maxPid;
            }
        }
    } catch (e) {
        // Swallow errors; no notification
    }
}

// Enhance init to also setup notifications
document.addEventListener('DOMContentLoaded', () => {
    if (window.__notifInit || document.getElementById('notif-panel')) {
        window.__notifInit = true;
        return;
    }
    initNotificationsUI();
    window.__notifInit = true;
    // Use a faster cadence for notifications than dashboard refresh
    checkNotifications();
    setInterval(checkNotifications, 15000);
});