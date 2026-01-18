let rowToComplete = null;
// View Rental Details
function viewRental(btn) {
    const row = btn.closest('tr');
    const cells = row.cells;

    document.getElementById('viewRentalId').textContent = cells[1].textContent;
    document.getElementById('viewCustomer').textContent = cells[2].querySelector('.customer-name').textContent;
    document.getElementById('viewBikeModel').textContent = cells[3].querySelector('.bike-model').textContent;
    document.getElementById('viewBikeId').textContent = cells[3].querySelector('.bike-id').textContent;
    document.getElementById('viewStartDate').textContent = cells[4].textContent;
    document.getElementById('viewReturnDate').textContent = cells[5].textContent;
    document.getElementById('viewDuration').textContent = cells[6].textContent;
    document.getElementById('viewAmount').textContent = cells[7].textContent;
    document.getElementById('viewStatus').innerHTML = cells[8].innerHTML;

    openModal('viewModal');
};

// Edit Rental
function editRental(btn) {
    const row = btn.closest('tr');
    const cells = row.cells;
    const rowIndex = Array.from(row.parentNode.children).indexOf(row);

    document.getElementById('editRowIndex').value = rowIndex;
    document.getElementById('editRentalId').value = cells[1].textContent;
    document.getElementById('editCustomer').value = cells[2].querySelector('.customer-name').textContent;
    document.getElementById('editBikeModel').value = cells[3].querySelector('.bike-model').textContent;
    document.getElementById('editBikeId').value = cells[3].querySelector('.bike-id').textContent;
    document.getElementById('editStartDate').value = '2025-11-26';
    document.getElementById('editReturnDate').value = '2025-11-28';
    document.getElementById('editDuration').value = cells[6].textContent;
    document.getElementById('editAmount').value = cells[7].textContent;
    
    const statusBadge = cells[8].querySelector('.status-badge');
    if (statusBadge.classList.contains('active')) {
        document.getElementById('editStatus').value = 'active';
    } else if (statusBadge.classList.contains('completed')) {
        document.getElementById('editStatus').value = 'completed';
    } else if (statusBadge.classList.contains('overdue')) {
        document.getElementById('editStatus').value = 'overdue';
    }

    openModal('editModal');
};

// Save Edit
function saveEdit() {
    const rowIndex = document.getElementById('editRowIndex').value;
    const tbody = document.getElementById('rentalTableBody');
    const row = tbody.children[rowIndex];

    const customer = document.getElementById('editCustomer').value;
    const bikeModel = document.getElementById('editBikeModel').value;
    const bikeId = document.getElementById('editBikeId').value;
    const startDate = document.getElementById('editStartDate').value;
    const returnDate = document.getElementById('editReturnDate').value;
    const duration = document.getElementById('editDuration').value;
    const amount = document.getElementById('editAmount').value;
    const status = document.getElementById('editStatus').value;

    // Update row
    row.cells[2].querySelector('.customer-name').textContent = customer;
    row.cells[3].querySelector('.bike-model').textContent = bikeModel;
    row.cells[3].querySelector('.bike-id').textContent = bikeId;
    row.cells[4].textContent = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    row.cells[5].textContent = new Date(returnDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    row.cells[6].textContent = duration;
    row.cells[7].querySelector('.amount').textContent = amount;

    // Update status badge
    const statusBadge = row.cells[8].querySelector('.status-badge');
    statusBadge.className = `status-badge ${status}`;
    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);

    // Update row class for overdue
    if (status === 'overdue') {
        row.classList.add('overdue-row');
    } else {
        row.classList.remove('overdue-row');
    }

    closeModal('editModal');
    alert('Rental updated successfully!');
};

// Open Complete Modal
function openCompleteModal(btn) {
    rowToComplete = btn.closest('tr');
    const cells = rowToComplete.cells;

    document.getElementById('completeRentalId').textContent = cells[1].textContent;
    document.getElementById('completeCustomer').textContent = cells[2].querySelector('.customer-name').textContent;
    document.getElementById('completeBike').textContent = cells[3].querySelector('.bike-model').textContent;
    document.getElementById('completeAmount').textContent = cells[7].textContent;

    openModal('completeModal');
}

// Confirm Complete
function confirmComplete() {
    if (rowToComplete) {
        rowToComplete.remove();
        rowToComplete = null;
        closeModal('completeModal');
        alert('Rental completed and removed from active rentals!');
    }
};

// Modal Functions
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
};

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
};

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
};