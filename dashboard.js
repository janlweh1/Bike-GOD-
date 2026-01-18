// Dashboard Data Management
let allBikes = [];
let activeRentals = [];
let rentalHistory = [];

// Initialize dashboard
function initDashboard() {
    loadBikesData();
    loadRentalData();
    updateStatistics();
    displayRecentRentals();
    displayAvailableBikes();
}

// Load bikes from shared-bikes-data.js
function loadBikesData() {
    if (typeof getBikesArray === 'function') {
        allBikes = getBikesArray();
    } else {
        console.warn('getBikesArray not found, using fallback data');
        allBikes = [];
    }
}

// Load rental data from localStorage
function loadRentalData() {
    try {
        const storedActiveRentals = localStorage.getItem('activeRentals');
        const storedHistory = localStorage.getItem('rentalHistory');
        
        if (storedActiveRentals) {
            activeRentals = JSON.parse(storedActiveRentals);
        }
        
        if (storedHistory) {
            rentalHistory = JSON.parse(storedHistory);
        }
    } catch (error) {
        console.error('Error loading rental data:', error);
        activeRentals = [];
        rentalHistory = [];
    }
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
    const thisMonth = Math.floor(allBikes.length * 0.08); // Approximate 8% as new
    
    document.getElementById('totalBikes').textContent = total;
    document.getElementById('bikesChange').textContent = `+${thisMonth} this month`;
}

// Update active rentals count
function updateActiveRentals() {
    const activeCount = activeRentals.length;
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
    const uniqueCustomers = new Set();
    
    // Add customers from active rentals
    activeRentals.forEach(rental => {
        if (rental.customerName) {
            uniqueCustomers.add(rental.customerName);
        }
    });
    
    // Add customers from history
    rentalHistory.forEach(rental => {
        if (rental.customerName) {
            uniqueCustomers.add(rental.customerName);
        }
    });
    
    const total = uniqueCustomers.size || (activeRentals.length + rentalHistory.length);
    const thisWeek = Math.floor(total * 0.05); // Approximate 5% as new this week
    
    document.getElementById('totalCustomers').textContent = total;
    document.getElementById('customersChange').textContent = `+${thisWeek} this week`;
}

// Update revenue today
function updateRevenueToday() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let revenue = 0;
    
    // Check completed rentals today
    rentalHistory.forEach(rental => {
        if (rental.endTime) {
            const endDate = new Date(rental.endTime);
            endDate.setHours(0, 0, 0, 0);
            if (endDate.getTime() === today.getTime()) {
                revenue += rental.cost || 0;
            }
        }
    });
    
    // Check active rentals that started today
    activeRentals.forEach(rental => {
        const startDate = new Date(rental.startTime);
        startDate.setHours(0, 0, 0, 0);
        if (startDate.getTime() === today.getTime()) {
            revenue += rental.cost || 0;
        }
    });
    
    const percentage = revenue > 0 ? '+15%' : '+0%';
    
    document.getElementById('revenueToday').textContent = `₱${revenue.toFixed(0)}`;
    document.getElementById('revenueChange').textContent = percentage;
}

// Display recent rentals in table
function displayRecentRentals() {
    const tbody = document.getElementById('recentRentalsTable');
    
    // Combine active rentals and recent history
    let allRentals = [];
    
    // Add active rentals
    activeRentals.forEach((rental, index) => {
        allRentals.push({
            ...rental,
            status: 'active',
            rentalId: rental.id || 'R' + String(Date.now() + index).slice(-4)
        });
    });
    
    // Add from history (most recent first)
    const recentHistory = rentalHistory.slice(0, 5);
    recentHistory.forEach((rental, index) => {
        allRentals.push({
            ...rental,
            rentalId: rental.id || 'R' + String(1000 + index),
            status: rental.status || 'completed'
        });
    });
    
    if (allRentals.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 2rem; color: #7f8c8d;">
                    No rentals yet
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = allRentals.slice(0, 5).map(rental => {
        const startDate = formatDate(rental.startTime);
        const returnDate = rental.endTime ? formatDate(rental.endTime) : 
                          formatDate(new Date(new Date(rental.startTime).getTime() + rental.duration * 3600000));
        
        const statusClass = rental.status === 'active' ? 'active' : 
                           rental.status === 'completed' ? 'completed' : 'overdue';
        const statusText = rental.status === 'active' ? 'Active' : 
                          rental.status === 'completed' ? 'Completed' : 'Overdue';
        
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
    loadBikesData();
    loadRentalData();
    updateStatistics();
    displayRecentRentals();
    displayAvailableBikes();
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