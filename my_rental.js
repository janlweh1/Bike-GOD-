// Get rental data from localStorage
let activeRentals = [];
let rentalHistory = [];
let timerIntervals = {};
let currentExtendRentalId = null;
let currentExtendPrice = 50;

// Load rental data on page load
function loadRentalData() {
    try {
        const storedRentals = localStorage.getItem('activeRentals');
        const storedHistory = localStorage.getItem('rentalHistory');
        
        if (storedRentals) {
            activeRentals = JSON.parse(storedRentals);
            displayActiveRentals();
            startAllTimers();
        } else {
            document.getElementById('noActiveRental').style.display = 'block';
        }
        
        if (storedHistory) {
            rentalHistory = JSON.parse(storedHistory);
        }
        
        updateActiveRentalCount();
        displayHistory();
        updateStatistics();
    } catch (error) {
        console.error('Error loading rental data:', error);
        document.getElementById('noActiveRental').style.display = 'block';
    }
}

// Update active rental count
function updateActiveRentalCount() {
    const countElement = document.getElementById('activeRentalCount');
    if (countElement) {
        countElement.textContent = activeRentals.length;
    }
}

// Format date and time
function formatDateTime(date) {
    const d = new Date(date);
    const dateStr = d.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
    const timeStr = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    return { date: dateStr, time: timeStr };
}

// Check if rental has started
function hasRentalStarted(rental) {
    try {
        const now = new Date();
        const scheduledStart = new Date(rental.pickupDate + ' ' + rental.pickupTime);
        return now >= scheduledStart;
    } catch (error) {
        console.error('Error checking rental start time:', error);
        return true;
    }
}

// Display active rentals
function displayActiveRentals() {
    try {
        const grid = document.getElementById('activeRentalsGrid');
        
        if (!grid) {
            console.error('activeRentalsGrid element not found');
            return;
        }
        
        if (activeRentals.length === 0) {
            grid.innerHTML = '';
            document.getElementById('noActiveRental').style.display = 'block';
            return;
        }
        
        document.getElementById('noActiveRental').style.display = 'none';
        
        grid.innerHTML = activeRentals.map(rental => {
            const startTime = new Date(rental.startTime);
            const formatted = formatDateTime(startTime);
            const scheduledStart = new Date(rental.pickupDate + ' ' + rental.pickupTime);
            const scheduledFormatted = formatDateTime(scheduledStart);
            const rentalStarted = hasRentalStarted(rental);
            
            // Calculate elapsed time only if rental has started
            let progressPercent = 0;
            let elapsedFormatted = '0:00';
            
            if (rentalStarted) {
                const now = new Date();
                const elapsed = Math.floor((now - scheduledStart) / 1000);
                const totalSeconds = rental.duration * 3600;
                
                // Calculate progress percentage
                progressPercent = Math.min(100, (elapsed / totalSeconds) * 100);
                
                // Format elapsed time
                const elapsedHours = Math.floor(elapsed / 3600);
                const elapsedMinutes = Math.floor((elapsed % 3600) / 60);
                elapsedFormatted = `${elapsedHours}:${String(elapsedMinutes).padStart(2, '0')}`;
            }
            
            return `
                <div class="active-rental-card" data-rental-id="${rental.id}">
                    <div class="rental-card-content">
                        <div class="bike-image-container">
                            <img src="${rental.image}" alt="${rental.name}" class="bike-image">
                        </div>
                        
                        <div class="bike-details">
                            <h3 class="bike-name">${rental.name}</h3>
                            <p class="bike-type">${rental.category.charAt(0).toUpperCase() + rental.category.slice(1)} Bike</p>
                            <p class="rental-info">Booked: ${formatted.date} ${formatted.time}</p>
                            <p class="rental-info">Scheduled Start: ${scheduledFormatted.date} ${scheduledFormatted.time}</p>
                            <p class="rental-info">Location: ${rental.location || 'Downtown Station A'}</p>
                            <p class="rental-info">Total Cost: ₱${rental.cost.toFixed(2)}</p>
                        </div>

                        <div class="timer-section">
                            ${rentalStarted ? `
                                <p class="timer-label">Remaining Time</p>
                                <div class="timer timer-${rental.id}">--:--:--</div>
                                <div class="progress-bar">
                                    <div class="progress-fill progress-${rental.id}" style="width: ${progressPercent}%"></div>
                                </div>
                                <p class="time-used time-used-${rental.id}">${elapsedFormatted} of ${rental.duration} hours used</p>
                            ` : `
                                <p class="timer-label">Status</p>
                                <div class="timer" style="font-size: 1.5rem; color: #fbbf24;">Scheduled</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%; background: #fbbf24;"></div>
                                </div>
                                <p class="time-used">Rental starts at pickup time</p>
                            `}
                        </div>

                        <div class="action-buttons">
                            <button class="btn-extend" onclick="openExtendModal('${rental.id}')" ${!rentalStarted ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>Extend Rental</button>
                            <button class="btn-end" onclick="endRental('${rental.id}')">${rentalStarted ? 'End Rental' : 'Cancel Rental'}</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error displaying active rentals:', error);
    }
}

// Start all timers
function startAllTimers() {
    try {
        // Clear existing intervals
        Object.values(timerIntervals).forEach(interval => clearInterval(interval));
        timerIntervals = {};
        
        activeRentals.forEach(rental => {
            if (hasRentalStarted(rental)) {
                startTimer(rental);
            } else {
                // Check every 5 seconds if scheduled rental should start
                timerIntervals[rental.id] = setInterval(() => {
                    if (hasRentalStarted(rental)) {
                        clearInterval(timerIntervals[rental.id]);
                        displayActiveRentals();
                        startTimer(rental);
                    }
                }, 5000);
            }
        });
    } catch (error) {
        console.error('Error starting timers:', error);
    }
}

// Timer function for a specific rental
function startTimer(rental) {
    if (timerIntervals[rental.id]) {
        clearInterval(timerIntervals[rental.id]);
    }
    
    timerIntervals[rental.id] = setInterval(() => {
        try {
            const scheduledStart = new Date(rental.pickupDate + ' ' + rental.pickupTime);
            const now = new Date();
            
            const elapsed = Math.floor((now - scheduledStart) / 1000);
            const totalSeconds = rental.duration * 3600;
            const remainingSeconds = Math.max(0, totalSeconds - elapsed);
            
            // Update timer display
            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            
            const timerDisplay = document.querySelector(`.timer-${rental.id}`);
            if (timerDisplay) {
                timerDisplay.textContent = `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
            
            // Update progress bar
            const progressPercent = Math.min(100, (elapsed / totalSeconds) * 100);
            const progressFill = document.querySelector(`.progress-${rental.id}`);
            if (progressFill) {
                progressFill.style.width = progressPercent + '%';
            }
            
            // Update time used
            const elapsedHours = Math.floor(elapsed / 3600);
            const elapsedMinutes = Math.floor((elapsed % 3600) / 60);
            const timeUsed = document.querySelector(`.time-used-${rental.id}`);
            if (timeUsed) {
                timeUsed.textContent = `${elapsedHours}:${String(elapsedMinutes).padStart(2, '0')} of ${rental.duration} hours used`;
            }
            
            // If time is up, end rental
            if (remainingSeconds <= 0) {
                clearInterval(timerIntervals[rental.id]);
                delete timerIntervals[rental.id];
                alert(`Your rental time for ${rental.name} has expired!`);
                endRental(rental.id);
            }
        } catch (error) {
            console.error('Error in timer:', error);
        }
    }, 1000);
}

// End rental
function endRental(rentalId) {
    try {
        const rentalIndex = activeRentals.findIndex(r => r.id === rentalId);
        if (rentalIndex === -1) return;
        
        const rental = activeRentals[rentalIndex];
        const rentalStarted = hasRentalStarted(rental);
        
        const message = rentalStarted 
            ? `Are you sure you want to end the rental for ${rental.name}?`
            : `Are you sure you want to cancel the rental for ${rental.name}?`;
        
        if (!confirm(message)) {
            return;
        }
        
        const endTime = new Date();
        
        // Add to history
        const completedRental = {
            ...rental,
            endTime: endTime.toISOString(),
            actualDuration: rental.duration,
            status: rentalStarted ? 'completed' : 'cancelled'
        };
        
        rentalHistory.unshift(completedRental);
        localStorage.setItem('rentalHistory', JSON.stringify(rentalHistory));
        
        // Remove from active rentals
        activeRentals.splice(rentalIndex, 1);
        localStorage.setItem('activeRentals', JSON.stringify(activeRentals));
        
        // Clear timer
        if (timerIntervals[rentalId]) {
            clearInterval(timerIntervals[rentalId]);
            delete timerIntervals[rentalId];
        }
        
        // Reload page
        location.reload();
    } catch (error) {
        console.error('Error ending rental:', error);
        alert('Error ending rental. Please try again.');
    }
}

// Open extend modal
function openExtendModal(rentalId) {
    try {
        const rental = activeRentals.find(r => r.id === rentalId);
        if (!rental) return;
        
        if (!hasRentalStarted(rental)) {
            alert('You can only extend a rental after it has started.');
            return;
        }
        
        currentExtendRentalId = rentalId;
        currentExtendPrice = rental.price;
        document.getElementById('extendHours').value = 1;
        updateExtendCost();
        document.getElementById('extendModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error('Error opening extend modal:', error);
    }
}

// Close extend modal
function closeExtendModal() {
    document.getElementById('extendModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    currentExtendRentalId = null;
}

// Update extend cost
function updateExtendCost() {
    const hours = parseInt(document.getElementById('extendHours').value);
    const cost = hours * currentExtendPrice;
    document.getElementById('extendCost').textContent = '₱' + cost.toFixed(2);
}

// Confirm extension
function confirmExtension() {
    try {
        if (!currentExtendRentalId) return;
        
        const rentalIndex = activeRentals.findIndex(r => r.id === currentExtendRentalId);
        if (rentalIndex === -1) return;
        
        const additionalHours = parseInt(document.getElementById('extendHours').value);
        const additionalCost = additionalHours * activeRentals[rentalIndex].price;
        
        // Update duration and cost
        activeRentals[rentalIndex].duration += additionalHours;
        activeRentals[rentalIndex].cost += additionalCost;
        
        localStorage.setItem('activeRentals', JSON.stringify(activeRentals));
        
        alert(`Rental extended by ${additionalHours} hour(s)! Additional cost: ₱${additionalCost.toFixed(2)}`);
        closeExtendModal();
        displayActiveRentals();
        startAllTimers();
    } catch (error) {
        console.error('Error confirming extension:', error);
        alert('Error extending rental. Please try again.');
    }
}

// Display history
function displayHistory() {
    try {
        const tbody = document.getElementById('historyTableBody');
        
        if (!tbody) {
            console.error('historyTableBody element not found');
            return;
        }
        
        if (rentalHistory.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #64748b;">
                        No rental history yet. Start renting bikes to see your history here!
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = rentalHistory.map(rental => {
            const start = formatDateTime(rental.startTime);
            const end = rental.endTime ? formatDateTime(rental.endTime) : { time: 'Ongoing' };
            
            return `
                <tr>
                    <td>
                        <div class="date-time">
                            <div class="date">${start.date}</div>
                            <div class="time">${start.time} - ${end.time}</div>
                        </div>
                    </td>
                    <td>
                        <div class="bike-info">
                            <div class="bike-name-table">${rental.name}</div>
                            <div class="bike-type-table">${rental.category.charAt(0).toUpperCase() + rental.category.slice(1)} Bike</div>
                        </div>
                    </td>
                    <td>${rental.actualDuration || rental.duration} hour(s)</td>
                    <td>${rental.location || 'Downtown Station A'}</td>
                    <td class="cost">₱${rental.cost.toFixed(2)}</td>
                    <td><span class="status-badge ${rental.status}">${rental.status.charAt(0).toUpperCase() + rental.status.slice(1)}</span></td>
                </tr>
            `;
        }).join('');
    } catch (error) {
        console.error('Error displaying history:', error);
    }
}

// Update statistics
function updateStatistics() {
    try {
        const totalRides = rentalHistory.length;
        const totalHours = rentalHistory.reduce((sum, r) => sum + (r.actualDuration || r.duration), 0);
        const totalSpent = rentalHistory.reduce((sum, r) => sum + (r.cost || 0), 0);
        
        const ridesElement = document.getElementById('totalRides');
        const hoursElement = document.getElementById('totalHours');
        const spentElement = document.getElementById('totalSpent');
        
        if (ridesElement) ridesElement.textContent = totalRides;
        if (hoursElement) hoursElement.textContent = totalHours;
        if (spentElement) spentElement.textContent = '₱' + totalSpent.toFixed(2);
    } catch (error) {
        console.error('Error updating statistics:', error);
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    loadRentalData();
    
    // Extend modal controls
    const closeBtn = document.getElementById('closeExtendModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeExtendModal);
    }
    
    const extendModal = document.getElementById('extendModal');
    if (extendModal) {
        extendModal.addEventListener('click', (e) => {
            if (e.target === extendModal) {
                closeExtendModal();
            }
        });
    }
    
    // Duration controls for extend modal
    const decreaseBtn = document.getElementById('decreaseExtendBtn');
    const increaseBtn = document.getElementById('increaseExtendBtn');
    const extendInput = document.getElementById('extendHours');
    
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', () => {
            const current = parseInt(extendInput.value);
            if (current > 1) {
                extendInput.value = current - 1;
                updateExtendCost();
            }
        });
    }
    
    if (increaseBtn) {
        increaseBtn.addEventListener('click', () => {
            const current = parseInt(extendInput.value);
            if (current < 24) {
                extendInput.value = current + 1;
                updateExtendCost();
            }
        });
    }
    
    const confirmBtn = document.getElementById('confirmExtendBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmExtension);
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#historyTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});