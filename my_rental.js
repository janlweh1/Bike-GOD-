// Get rental data from localStorage
let activeRentals = [];
let rentalHistory = [];
let timerIntervals = {};
let currentExtendRentalId = null;
let currentExtendPrice = 50;
// State for end/cancel choice modal
let pendingEndRentalIndex = null;
let pendingEndAction = null; // 'complete' or 'cancel'

// Load rental data on page load
async function loadRentalData() {
    try {
        const storedRentals = localStorage.getItem('activeRentals');
        const storedHistory = localStorage.getItem('rentalHistory');

        activeRentals = storedRentals ? JSON.parse(storedRentals) : [];
        rentalHistory = storedHistory ? JSON.parse(storedHistory) : [];

        // Strip deprecated location field from any existing entries
        stripLocationFields();

        // Attempt to sync with server to reflect admin updates
        await syncWithServer();

        if (activeRentals.length > 0) {
            displayActiveRentals();
            startAllTimers();
            document.getElementById('noActiveRental').style.display = 'none';
        } else {
            document.getElementById('noActiveRental').style.display = 'block';
        }

        updateActiveRentalCount();
        displayHistory();
        updateStatistics();
    } catch (error) {
        console.error('Error loading rental data:', error);
        document.getElementById('noActiveRental').style.display = 'block';
    }
}

// Remove location properties from stored rentals/history for consistency
function stripLocationFields() {
    let changed = false;
    activeRentals.forEach(r => { if ('location' in r) { delete r.location; changed = true; } });
    rentalHistory.forEach(r => { if ('location' in r) { delete r.location; changed = true; } });
    if (changed) {
        try {
            localStorage.setItem('activeRentals', JSON.stringify(activeRentals));
            localStorage.setItem('rentalHistory', JSON.stringify(rentalHistory));
        } catch (e) {
            // ignore storage errors
        }
    }
}

// Sync local rentals with backend statuses so admin completions end member rentals
async function syncWithServer() {
    try {
        const sessRes = await fetch('check_session.php', { credentials: 'include' });
        const sess = await sessRes.json();
        if (!sess || sess.loggedIn !== true || sess.userType !== 'member') {
            return; // not logged as member; skip
        }
        const res = await fetch('get_member_rentals.php', { credentials: 'include', cache: 'no-store' });
        const data = await res.json();
        if (!data || data.success !== true || !Array.isArray(data.rentals)) {
            return;
        }
        const serverById = new Map();
        data.rentals.forEach(r => {
            const idStr = String(r.rentalId);
            serverById.set(idStr, r);
        });
        // Move any locally active rental to history if server says completed/cancelled
        let changed = false;
        activeRentals = activeRentals.filter(r => {
            const srv = serverById.get(String(r.id));
            if (!srv) return true; // keep if no server info
            const st = (srv.status || '').toLowerCase();
            if (st === 'completed' || st === 'cancelled') {
                const endTimeIso = srv.returnDate ? (srv.returnDate + 'T00:00:00.000Z') : new Date().toISOString();
                const completedRental = {
                    ...r,
                    endTime: endTimeIso,
                    actualDuration: r.duration,
                    status: st
                };
                rentalHistory.unshift(completedRental);
                changed = true;
                // Notify the user about server-side completion/cancellation
                const verb = st === 'completed' ? 'completed by admin' : 'cancelled by admin';
                showToast(`Your rental for ${r.name} was ${verb}.`);
                return false; // remove from active
            }
            return true;
        });
        if (changed) {
            localStorage.setItem('activeRentals', JSON.stringify(activeRentals));
            localStorage.setItem('rentalHistory', JSON.stringify(rentalHistory));
        }
    } catch (e) {
        // Silent fail; keep local view
        console.warn('Server sync failed:', e);
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

        // Determine whether this is an early end (before planned duration is used)
        let isEarly = false;
        let remainingSeconds = 0;
        if (rentalStarted) {
            try {
                const now = new Date();
                const scheduledStart = new Date(rental.pickupDate + ' ' + rental.pickupTime);
                const elapsed = Math.max(0, Math.floor((now - scheduledStart) / 1000));
                const totalSeconds = (rental.duration || 0) * 3600;
                remainingSeconds = Math.max(0, totalSeconds - elapsed);
                isEarly = remainingSeconds > 0;
            } catch (e) {
                console.warn('Failed to compute early-end status:', e);
            }
        }

        pendingEndRentalIndex = rentalIndex;
        pendingEndAction = null;

        const modal = document.getElementById('endChoiceModal');
        const titleEl = document.getElementById('endChoiceTitle');
        const subtitleEl = document.getElementById('endChoiceSubtitle');
        const bikeNameEl = document.getElementById('endChoiceBikeName');
        const timeRowEl = document.getElementById('endChoiceTimeRow');
        const timeRemainingEl = document.getElementById('endChoiceTimeRemaining');

        if (!modal) {
            console.error('endChoiceModal element not found');
            return;
        }

        bikeNameEl.textContent = rental.name;

        if (!rentalStarted) {
            // Future booking that hasn't started yet → simple cancel modal
            titleEl.textContent = 'Cancel Rental';
            subtitleEl.textContent = 'This rental has not started yet. You can cancel it with no ride time used.';
            if (timeRowEl) timeRowEl.style.display = 'none';
            pendingEndAction = 'cancel';
        } else if (isEarly) {
            // Rental already started but still has remaining time → show remaining
            titleEl.textContent = 'End Rental Early?';
            subtitleEl.textContent = 'You still have remaining time on this rental. You can end your ride now or cancel the rental.';
            if (timeRowEl) timeRowEl.style.display = '';

            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            timeRemainingEl.textContent = `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        } else {
            // Rental started and is at/after planned end → normal completion
            titleEl.textContent = 'End Rental';
            subtitleEl.textContent = 'Your booked time is finished. Ending now will complete this rental.';
            if (timeRowEl) timeRowEl.style.display = 'none';
            pendingEndAction = 'complete';
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error('Error ending rental:', error);
    }
}

// Actually submit the end/cancel decision to backend
function submitEndRental(actionOverride) {
    try {
        if (pendingEndRentalIndex === null || pendingEndRentalIndex < 0) return;
        const rental = activeRentals[pendingEndRentalIndex];
        if (!rental) return;

        const action = actionOverride || pendingEndAction || 'complete';

        const form = new URLSearchParams();
        form.set('rental_id', rental.id);
        form.set('action', action);

        fetch('member_complete_rental.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: form.toString()
        })
            .then(res => res.json())
            .then(data => {
                if (!data || data.success === false) {
                    throw new Error(data && data.message ? data.message : 'Failed to end rental');
                }

                const endTime = new Date();
                const finalStatus = data.status || (action === 'complete' ? 'completed' : 'cancelled');

                const completedRental = {
                    ...rental,
                    endTime: endTime.toISOString(),
                    actualDuration: rental.duration,
                    status: finalStatus
                };

                rentalHistory.unshift(completedRental);
                localStorage.setItem('rentalHistory', JSON.stringify(rentalHistory));

                activeRentals.splice(pendingEndRentalIndex, 1);
                localStorage.setItem('activeRentals', JSON.stringify(activeRentals));

                if (timerIntervals[rental.id]) {
                    clearInterval(timerIntervals[rental.id]);
                    delete timerIntervals[rental.id];
                }

                displayActiveRentals();
                updateActiveRentalCount();
                displayHistory();
                updateStatistics();

                closeEndChoiceModal();

                showToast(finalStatus === 'completed'
                    ? `Your rental for ${rental.name} has been ended.`
                    : `Your rental for ${rental.name} has been cancelled.`);
            })
            .catch(error => {
                console.error('Error ending rental:', error);
                alert('Error ending rental. Please try again.');
            });
    } catch (error) {
        console.error('Error ending rental:', error);
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
                    <td colspan="5" style="text-align: center; padding: 3rem; color: #64748b;">
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

    // End / cancel rental modal controls
    const endModal = document.getElementById('endChoiceModal');
    const closeEndBtn = document.getElementById('closeEndChoiceModal');
    const keepRidingBtn = document.getElementById('keepRidingBtn');
    const cancelRentalBtn = document.getElementById('cancelRentalBtn');
    const completeRentalBtn = document.getElementById('completeRentalBtn');

    if (closeEndBtn) {
        closeEndBtn.addEventListener('click', closeEndChoiceModal);
    }

    if (endModal) {
        endModal.addEventListener('click', (e) => {
            if (e.target === endModal) {
                closeEndChoiceModal();
            }
        });
    }

    if (keepRidingBtn) {
        keepRidingBtn.addEventListener('click', () => {
            closeEndChoiceModal();
        });
    }

    if (cancelRentalBtn) {
        cancelRentalBtn.addEventListener('click', () => {
            submitEndRental('cancel');
        });
    }

    if (completeRentalBtn) {
        completeRentalBtn.addEventListener('click', () => {
            submitEndRental('complete');
        });
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

// Periodically refresh to reflect admin completions quickly
setInterval(() => {
    loadRentalData();
}, 30000);

// Lightweight toast notification
function showToast(message) {
    try {
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.position = 'fixed';
        toast.style.right = '20px';
        toast.style.bottom = '20px';
        toast.style.background = '#1f2937';
        toast.style.color = '#fff';
        toast.style.padding = '12px 16px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0 6px 20px rgba(0,0,0,0.25)';
        toast.style.fontSize = '14px';
        toast.style.zIndex = '9999';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        toast.style.transform = 'translateY(10px)';
        document.body.appendChild(toast);
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => { toast.remove(); }, 200);
        }, 4000);
    } catch (e) {
        // ignore toast errors
    }
}

function closeEndChoiceModal() {
    const modal = document.getElementById('endChoiceModal');
    if (modal) {
        modal.classList.remove('active');
    }
    document.body.style.overflow = 'auto';
    pendingEndRentalIndex = null;
    pendingEndAction = null;
}