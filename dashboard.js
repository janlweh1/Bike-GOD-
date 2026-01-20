// Dashboard Data Management
let allBikes = [];
let activeRentals = [];
let rentalHistory = [];
let rentalsSummary = null;

// Initialize dashboard
function initDashboard() {
    Promise.all([loadBikesData(), loadRentalData(), loadCustomersSummary()])
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
        const startDate = formatDate(rental.startTime || new Date());
        const returnDate = rental.endTime ? formatDate(rental.endTime) : startDate;
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

// Refresh dashboard data (call this periodically or on page visibility)
function refreshDashboard() {
    Promise.all([loadBikesData(), loadRentalData(), loadCustomersSummary()])
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