// Global variables
let allRentals = [];
let filteredRentals = [];
let currentRentalForComplete = null;
let latestSummary = null;

// Load rental data on page load
function setFetchInfo(text) {
    const el = document.getElementById('rentalsFetchInfo');
    if (el) el.textContent = text || '';
}

function loadRentalData() {
    setFetchInfo('Loading...');
    // Prefer server data; fallback to local data if unavailable
    fetch('get_rentals.php?t=' + Date.now(), { credentials: 'include', cache: 'no-store' })
        .then(async res => {
            const ct = (res.headers.get('content-type') || '').toLowerCase();
            if (!ct.includes('application/json')) {
                const txt = await res.text();
                const snippet = (txt || '').slice(0, 300);
                throw new Error(`HTTP ${res.status} ${res.statusText} • Non-JSON: ${snippet}`);
            }
            const data = await res.json();
            if (!res.ok || !data || data.success === false) {
                const msg = (data && (data.message || data.error)) ? data.message || data.error : `HTTP ${res.status}`;
                throw new Error(msg);
            }
            return data;
        })
        .then(data => {
            const rentals = Array.isArray(data.rentals) ? data.rentals : [];
            // Normalize and sort
            allRentals = rentals.map(r => ({
                ...r,
                startTime: r.startTime || (r.pickupDate && r.pickupTime ? new Date(r.pickupDate + ' ' + r.pickupTime).toISOString() : null),
                cost: typeof r.cost === 'number' ? r.cost : 0,
                duration: typeof r.duration === 'number' ? r.duration : 0,
            }));
            allRentals.sort((a, b) => new Date(b.startTime || 0) - new Date(a.startTime || 0));
            filteredRentals = [...allRentals];
            latestSummary = data.summary || null;
            updateStatistics(latestSummary);
            displayRentals();
            const last = allRentals[0];
            setFetchInfo(`${allRentals.length} rental(s) loaded` + (last && last.id ? ` • Latest #${last.id}` : ''));
        })
        .catch(err => {
            console.error('Error loading rentals from server:', err);
            displayNoData();
            setFetchInfo('Failed to load.');
        });
}

// Calculate rental status based purely on server data
// The backend already derives the correct status using
// Rentals.status and Returns (including overdue logic),
// so the frontend just normalizes casing.
function calculateRentalStatus(rental) {
    const raw = (rental.status || '').toString().toLowerCase();
    if (!raw) return 'active';
    return raw;
}

// Calculate return time
function calculateReturnTime(rental) {
    try {
        // Prefer actual end time from server (based on Returns table)
        if (rental.endTime) {
            const actual = new Date(rental.endTime);
            return actual.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }

        // Fallback: planned return based on pickup date/time + duration
        if (rental.pickupDate && rental.pickupTime && rental.duration) {
            const scheduledStart = new Date(rental.pickupDate + ' ' + rental.pickupTime);
            const returnTime = new Date(scheduledStart);
            returnTime.setHours(returnTime.getHours() + rental.duration);
            return returnTime.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }

        return 'N/A';
    } catch (error) {
        return 'N/A';
    }
}

// Format date
function formatDate(dateString) {
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    } catch (error) {
        return 'N/A';
    }
}

// Format time
function formatTime(timeString) {
    try {
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(parseInt(hours), parseInt(minutes));
        return date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    } catch (error) {
        return timeString;
    }
}

// Update statistics
function updateStatistics(summary) {
    if (summary) {
        document.getElementById('activeCount').textContent = summary.active ?? 0;
        document.getElementById('completedTodayCount').textContent = summary.completedToday ?? 0;
        document.getElementById('overdueCount').textContent = summary.overdue ?? 0;
        return;
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let activeCount = 0;
    let completedTodayCount = 0;
    let overdueCount = 0;
    
    allRentals.forEach(rental => {
        const status = calculateRentalStatus(rental);
        
        if (status === 'active') {
            activeCount++;
        } else if (status === 'overdue') {
            overdueCount++;
        }
        
        // Check if completed today
        if (status === 'completed' && rental.endTime) {
            const endDate = new Date(rental.endTime);
            endDate.setHours(0, 0, 0, 0);
            
            if (endDate.getTime() === today.getTime()) {
                completedTodayCount++;
            }
        }
    });
    
    // Update DOM
    document.getElementById('activeCount').textContent = activeCount;
    document.getElementById('completedTodayCount').textContent = completedTodayCount;
    document.getElementById('overdueCount').textContent = overdueCount;
}

// Display rentals in table
function displayRentals() {
    const tbody = document.getElementById('rentalTableBody');
    
    if (filteredRentals.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" style="text-align: center; padding: 3rem; color: #64748b;">
                    No rentals found.
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = filteredRentals.map((rental, index) => {
        const status = calculateRentalStatus(rental);
        const returnTime = calculateReturnTime(rental);
        const rentalIdShort = rental.id ? rental.id.substring(0, 8) : 'N/A';
        
        const rowClass = status === 'overdue' ? 'overdue-row' : '';
        
        return `
            <tr class="${rowClass}" data-rental-index="${index}">
                <td>
                    <input type="checkbox" class="rental-checkbox">
                </td>
                <td><span class="rental-id">#${rentalIdShort}</span></td>
                <td>
                    <div class="customer-info">
                        <div class="customer-name">${rental.customerName || 'N/A'}</div>
                    </div>
                </td>
                <td>
                    <div class="bike-info">
                        <span class="bike-model">${rental.name}</span>
                        <span class="bike-id">${rental.category}</span>
                    </div>
                </td>
                <td>${formatDate(rental.pickupDate)}</td>
                <td>${formatTime(rental.pickupTime)}</td>
                <td>${returnTime}</td>
                <td>${rental.duration} hour(s)</td>
                <td><span class="amount">₱${rental.cost.toFixed(2)}</span></td>
                <td><span class="status-badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" title="View Details" onclick="viewRental(${index})">
                            <span class="icon"><img src="view.png"></span>
                        </button>
                        ${status === 'active' || status === 'overdue' ? `
                            <button class="action-btn complete" title="Complete Rental" onclick="openCompleteModal(${index})">
                                <span class="icon"><img src="check (1).png"></span>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Display no data message
function displayNoData() {
    const tbody = document.getElementById('rentalTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="11" style="text-align: center; padding: 3rem; color: #64748b;">
                No rental data available.
            </td>
        </tr>
    `;
}

// View rental details
function viewRental(index) {
    const rental = filteredRentals[index];
    const status = calculateRentalStatus(rental);
    const returnTime = calculateReturnTime(rental);
    
    document.getElementById('viewRentalId').textContent = '#' + (rental.id ? rental.id.substring(0, 8) : 'N/A');
    document.getElementById('viewStatus').innerHTML = `<span class="status-badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    document.getElementById('viewStartDate').textContent = formatDate(rental.pickupDate);
    document.getElementById('viewStartTime').textContent = formatTime(rental.pickupTime);
    document.getElementById('viewReturnTime').textContent = returnTime;
    document.getElementById('viewDuration').textContent = rental.duration + ' hour(s)';
    document.getElementById('viewAmount').textContent = '₱' + rental.cost.toFixed(2);
    document.getElementById('viewLocation').textContent = rental.location || 'Downtown Station A';
    
    document.getElementById('viewCustomer').textContent = rental.customerName || 'N/A';
    document.getElementById('viewEmail').textContent = rental.customerEmail || 'N/A';
    document.getElementById('viewPhone').textContent = rental.customerPhone || 'N/A';
    
    document.getElementById('viewBikeModel').textContent = rental.name;
    document.getElementById('viewBikeCategory').textContent = rental.category.charAt(0).toUpperCase() + rental.category.slice(1);
    
    openModal('viewModal');
}

// Open complete modal
function openCompleteModal(index) {
    const rental = filteredRentals[index];
    currentRentalForComplete = rental;
    
    const rentalIdShort = rental.id ? rental.id.substring(0, 8) : 'N/A';
    
    document.getElementById('completeRentalId').textContent = '#' + rentalIdShort;
    document.getElementById('completeCustomer').textContent = rental.customerName || 'N/A';
    document.getElementById('completeBike').textContent = rental.name;
    document.getElementById('completeAmount').textContent = '₱' + rental.cost.toFixed(2);
    
    openModal('completeModal');
}

// Confirm complete rental
function confirmComplete() {
    if (!currentRentalForComplete) return;
    const rid = currentRentalForComplete.id;
    const form = new URLSearchParams();
    form.set('rental_id', rid);
    fetch('complete_rental.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form.toString()
    })
        .then(res => res.json())
        .then(data => {
            if (!data || data.success === false) throw new Error(data && data.message ? data.message : 'Failed');
            alert('Rental completed successfully!');
            closeModal('completeModal');
            currentRentalForComplete = null;
            loadRentalData();
        })
        .catch(err => {
            console.error('Error completing rental:', err);
            alert('Error completing rental. Please try again.');
        });
}


// Apply filters
function applyFilters() {
    const statusFilter = document.getElementById('filterStatus').value;
    const dateFilter = document.getElementById('filterDate').value;
    const bikeTypeFilter = document.getElementById('filterBikeType').value;
    const sortFilter = document.getElementById('filterSort').value;
    
    // Filter by status
    filteredRentals = allRentals.filter(rental => {
        if (statusFilter && calculateRentalStatus(rental) !== statusFilter) {
            return false;
        }
        return true;
    });
    
    // Filter by date range
    if (dateFilter) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        filteredRentals = filteredRentals.filter(rental => {
            const rentalDate = new Date(rental.pickupDate);
            rentalDate.setHours(0, 0, 0, 0);
            
            if (dateFilter === 'today') {
                return rentalDate.getTime() === today.getTime();
            } else if (dateFilter === 'week') {
                const weekAgo = new Date(today);
                weekAgo.setDate(weekAgo.getDate() - 7);
                return rentalDate >= weekAgo;
            } else if (dateFilter === 'month') {
                const monthAgo = new Date(today);
                monthAgo.setMonth(monthAgo.getMonth() - 1);
                return rentalDate >= monthAgo;
            }
            return true;
        });
    }
    
    // Filter by bike type
    if (bikeTypeFilter) {
        filteredRentals = filteredRentals.filter(rental => {
            return rental.category.toLowerCase() === bikeTypeFilter.toLowerCase();
        });
    }
    
    // Sort
    if (sortFilter === 'recent') {
        filteredRentals.sort((a, b) => new Date(b.startTime) - new Date(a.startTime));
    } else if (sortFilter === 'oldest') {
        filteredRentals.sort((a, b) => new Date(a.startTime) - new Date(b.startTime));
    } else if (sortFilter === 'return-date') {
        filteredRentals.sort((a, b) => {
            const returnA = new Date(a.pickupDate + ' ' + a.pickupTime);
            returnA.setHours(returnA.getHours() + a.duration);
            const returnB = new Date(b.pickupDate + ' ' + b.pickupTime);
            returnB.setHours(returnB.getHours() + b.duration);
            return returnA - returnB;
        });
    } else if (sortFilter === 'amount') {
        filteredRentals.sort((a, b) => b.cost - a.cost);
    }
    
    displayRentals();
}

// Search rentals
function searchRentals(searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    
    if (!searchTerm) {
        filteredRentals = [...allRentals];
    } else {
        filteredRentals = allRentals.filter(rental => {
            const rentalId = rental.id ? rental.id.toLowerCase() : '';
            const customerName = rental.customerName ? rental.customerName.toLowerCase() : '';
            const bikeName = rental.name ? rental.name.toLowerCase() : '';
            
            return rentalId.includes(searchTerm) || 
                   customerName.includes(searchTerm) || 
                   bikeName.includes(searchTerm);
        });
    }
    
    displayRentals();
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadRentalData();
    const rbtn = document.getElementById('refreshRentalsBtn');
    if (rbtn) rbtn.addEventListener('click', loadRentalData);
    
    // Filter event listeners
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterDate').addEventListener('change', applyFilters);
    document.getElementById('filterBikeType').addEventListener('change', applyFilters);
    document.getElementById('filterSort').addEventListener('change', applyFilters);
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', (e) => {
        searchRentals(e.target.value);
    });
    
    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', (e) => {
        const checkboxes = document.querySelectorAll('.rental-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('show');
        }
    };
    
    // Refresh data every 30 seconds to update statuses
    setInterval(() => {
        loadRentalData();
    }, 30000);
});