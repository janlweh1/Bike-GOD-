// Server-backed Admin Bikes page

let serverBikes = [];
let uploadedImageData = null;

function mapDbTypeToCategory(dbType) {
    const t = String(dbType || '').toLowerCase();
    if (t.includes('city')) return 'city';
    if (t.includes('mountain')) return 'mountain';
    if (t.includes('electric')) return 'electric';
    if (t.includes('kid')) return 'kids';
    return 'premium';
}

function mapCategoryToDbType(category) {
    switch (category) {
        case 'city': return 'City Bike';
        case 'mountain': return 'Mountain Bike';
        case 'electric': return 'Electric Bike';
        case 'kids': return 'Kids Bike';
        case 'premium': return 'Road Bike';
        default: return category;
    }
}

function createBikeCard(bike) {
    const status = (bike.status || 'available').toLowerCase();
    const category = bike.category || 'city';
    const price = typeof bike.price === 'number' ? bike.price : 0;
    const image = bike.image || 'https://via.placeholder.com/500x300/cccccc/666666?text=Bike';
    const statusLabel = status === 'available' ? 'Available' : (status === 'rented' ? 'Rented' : (status.charAt(0).toUpperCase() + status.slice(1)));
    const actions = status === 'maintenance'
        ? `<button class="edit-btn" onclick="restoreBike(${bike.id})">Restore</button>
           <button class="delete-btn" onclick="openDeleteModal(${bike.id})">Delete</button>`
        : `<button class="edit-btn" onclick="openEditModal(${bike.id})">Edit</button>
           <button class="delete-btn" onclick="openDeleteModal(${bike.id})">Delete</button>`;
    return `
        <div class="bike-card" data-id="${bike.id}" data-category="${category}" data-status="${status}" data-price="${price}">
            <div class="bike-image">
                <img src="${image}" alt="${bike.name}">
                <span class="bike-id">#${bike.id}</span>
                <span class="status-badge ${status}">${statusLabel}</span>
            </div>
            <div class="bike-info">
                <h3>${bike.name}</h3>
                <p class="bike-type">${category.charAt(0).toUpperCase() + category.slice(1)} Bike</p>
                <div class="bike-specs">
                    <span>⚙️ Type: ${bike.type || '-'}</span>
                </div>
                <div class="bike-footer">
                    <div class="price-section">
                        <div class="price">₱${price}<span>/hour</span></div>
                    </div>
                    <div class="bike-actions">
                        ${actions}
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderBikes(list = serverBikes) {
    const grid = document.getElementById('bikesGrid');
    if (!Array.isArray(list) || list.length === 0) {
        grid.innerHTML = '<p style="text-align: center; color: #7f8c8d; grid-column: 1/-1; padding: 40px;">No bikes found.</p>';
        return;
    }
    grid.innerHTML = list.map(createBikeCard).join('');
}

function filterBikes() {
    const statusFilter = document.getElementById('statusFilter').value; // available|rented|all
    const typeFilter = document.getElementById('typeFilter').value;     // category values
    const priceFilter = document.getElementById('priceFilter').value;
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();

    const filtered = serverBikes.filter(bike => {
        if (statusFilter !== 'all' && bike.status !== statusFilter) return false;
        // By default, hide maintenance/archived bikes when "all" is selected
        if (statusFilter === 'all' && bike.status && !['available','rented'].includes(bike.status)) return false;
        if (typeFilter !== 'all' && bike.category !== typeFilter) return false;

        if (priceFilter !== 'all') {
            const p = bike.price || 0;
            if (priceFilter === '0-100' && (p < 0 || p > 100)) return false;
            if (priceFilter === '100-150' && (p < 100 || p > 150)) return false;
            if (priceFilter === '150-200' && (p < 150 || p > 200)) return false;
            if (priceFilter === '200+' && p < 200) return false;
        }

        if (searchQuery) {
            const hay = `${String(bike.name).toLowerCase()} ${String(bike.id).toLowerCase()} ${String(bike.type).toLowerCase()}`;
            if (!hay.includes(searchQuery)) return false;
        }
        return true;
    });

    renderBikes(filtered);
}

async function loadBikes() {
    const grid = document.getElementById('bikesGrid');
    grid.innerHTML = '<p style="text-align:center; grid-column:1/-1; padding: 40px;">Loading...</p>';
    try {
        const res = await fetch('get_bikes.php');
        const data = await res.json();
        if (!data.success) throw new Error('Failed to load bikes');
        serverBikes = (data.bikes || []).map(b => {
            const category = mapDbTypeToCategory(b.type);
            const status = String(b.availability || 'Available').toLowerCase();
            return {
                id: b.id,
                name: b.model,
                type: b.type,
                category,
                status,
                price: Number(b.hourly_rate) || 0,
                image: null
            };
        });
        filterBikes();
    } catch (e) {
        console.error(e);
        grid.innerHTML = '<p style="text-align:center; grid-column:1/-1; padding: 40px; color:#c0392b;">Error loading bikes.</p>';
    }
}

function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
    uploadedImageData = null;
    resetAddImageUpload();
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    document.getElementById('addBikeForm').reset();
    uploadedImageData = null;
    resetAddImageUpload();
}

function setupAddImageUpload() {
    const uploadArea = document.getElementById('addImageUploadArea');
    const fileInput = document.getElementById('addBikeImage');
    const previewContainer = document.getElementById('addImagePreview');
    const previewImg = previewContainer.querySelector('img');

    uploadArea.addEventListener('click', function(e) {
        if (!e.target.closest('.remove-image-btn')) {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                uploadedImageData = e.target.result;
                previewImg.src = uploadedImageData;
                uploadArea.querySelector('.upload-placeholder').style.display = 'none';
                previewContainer.classList.add('active');
            };
            reader.readAsDataURL(file);
        }
    });
}

function removeAddImage() {
    uploadedImageData = null;
    resetAddImageUpload();
}

function resetAddImageUpload() {
    const uploadArea = document.getElementById('addImageUploadArea');
    const previewContainer = document.getElementById('addImagePreview');
    const fileInput = document.getElementById('addBikeImage');
    if (!uploadArea || !previewContainer || !fileInput) return;
    uploadArea.querySelector('.upload-placeholder').style.display = 'flex';
    previewContainer.classList.remove('active');
    fileInput.value = '';
}

function generateTempDisplayId() {
    const max = serverBikes.reduce((m, b) => Math.max(m, Number(b.id) || 0), 0);
    return `B${String(max + 1).padStart(3, '0')}`;
}

async function handleAddBikeSubmit(e) {
    e.preventDefault();

    const idInput = document.getElementById('addBikeId');
    if (idInput && !idInput.value) {
        idInput.value = generateTempDisplayId();
    }

    const bikeName = document.getElementById('addBikeModel').value.trim();
    const bikeTypeCategory = document.getElementById('addBikeType').value; // category values
    const bikeStatus = document.getElementById('addBikeStatus').value; // available|rented
    const bikePrice = document.getElementById('addBikePrice').value;

    if (!bikeName || !bikeTypeCategory || !bikeStatus || bikePrice === '') {
        alert('Please fill in all required fields.');
        return;
    }

    const form = new FormData();
    form.append('model', bikeName);
    form.append('type', mapCategoryToDbType(bikeTypeCategory));
    form.append('status', bikeStatus);
    form.append('rate', String(bikePrice));

    try {
        const res = await fetch('add_bike.php', {
            method: 'POST',
            body: form
        });
        const data = await res.json();
        if (!data.success) {
            const msg = data.message || data.error || 'Failed to add bike';
            alert(msg);
            return;
        }
        closeAddModal();
        await loadBikes();
        alert('Bike added successfully!');
    } catch (err) {
        console.error(err);
        alert('Network error while adding bike');
    }
}

// Edit flow
let currentEditBike = null;

function openEditModal(id) {
    const bike = serverBikes.find(b => Number(b.id) === Number(id));
    if (!bike) return;
    currentEditBike = bike;
    document.getElementById('editBikeId').value = bike.id;
    document.getElementById('editBikeModel').value = bike.name || '';
    document.getElementById('editBikeType').value = bike.category || 'city';
    document.getElementById('editBikeStatus').value = bike.status || 'available';
    document.getElementById('editBikePrice').value = typeof bike.price === 'number' ? bike.price : 0;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    currentEditBike = null;
}

async function handleEditBikeSubmit(e) {
    e.preventDefault();
    if (!currentEditBike) return;
    const id = Number(document.getElementById('editBikeId').value);
    const model = document.getElementById('editBikeModel').value.trim();
    const category = document.getElementById('editBikeType').value;
    const status = document.getElementById('editBikeStatus').value;
    const rate = document.getElementById('editBikePrice').value;
    const form = new FormData();
    form.append('id', String(id));
    if (model) form.append('model', model);
    if (category) form.append('type', mapCategoryToDbType(category));
    if (status) form.append('status', status);
    if (rate !== '') form.append('rate', String(rate));
    try {
        const res = await fetch('update_bike.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.success) {
            alert((data.message || data.error || 'Update failed'));
            return;
        }
        closeEditModal();
        await loadBikes();
        alert('Bike updated successfully!');
    } catch (err) {
        console.error(err);
        alert('Network error while updating bike');
    }
}

// Delete flow
let currentDeleteBikeId = null;
let currentDeleteBikeName = '';

function openDeleteModal(id) {
    const bike = serverBikes.find(b => Number(b.id) === Number(id));
    if (!bike) return;
    currentDeleteBikeId = id;
    currentDeleteBikeName = bike.name || '';
    const el = document.querySelector('.delete-bike-name');
    if (el) el.textContent = `${currentDeleteBikeName} (#${id})`;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentDeleteBikeId = null;
    currentDeleteBikeName = '';
}

async function confirmDelete() {
    if (!currentDeleteBikeId) return;
    const bike = serverBikes.find(b => Number(b.id) === Number(currentDeleteBikeId));
    const form = new FormData();
    form.append('id', String(currentDeleteBikeId));
    try {
        const res = await fetch('delete_bike.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.success) {
            // If the bike is already in maintenance, do not fallback; show hard delete failure
            if (bike && bike.status === 'maintenance') {
                const doCascade = confirm('This maintenance bike still has related rentals/returns.\nDelete permanently with all related records?');
                if (!doCascade) return;
                const cascadeForm = new FormData();
                cascadeForm.append('id', String(currentDeleteBikeId));
                cascadeForm.append('cascade', '1');
                const resC = await fetch('delete_bike.php', { method: 'POST', body: cascadeForm });
                const dataC = await resC.json();
                if (!dataC.success) { alert('Cascade delete failed.'); return; }
                closeDeleteModal();
                await loadBikes();
                alert('Bike and related records deleted permanently.');
                return;
            }
            // Otherwise, offer to archive
            const proceedArchive = confirm('This bike has rental history and cannot be deleted.\nWould you like to archive it instead?');
            if (!proceedArchive) return;
            const upd = new FormData();
            upd.append('id', String(currentDeleteBikeId));
            upd.append('status', 'maintenance');
            const res2 = await fetch('update_bike.php', { method: 'POST', body: upd });
            const data2 = await res2.json();
            if (!data2.success) { alert('Archive failed.'); return; }
            closeDeleteModal();
            await loadBikes();
            alert('Bike archived (set to Maintenance).');
            return;
        }
        closeDeleteModal();
        await loadBikes();
        alert('Bike deleted successfully!');
    } catch (err) {
        console.error(err);
        alert('Network error while deleting bike');
    }
}

// Restore a maintenance bike back to available
async function restoreBike(id) {
    const confirmRestore = confirm('Restore this bike to Available?');
    if (!confirmRestore) return;
    try {
        const form = new FormData();
        form.append('id', String(id));
        form.append('status', 'available');
        const res = await fetch('update_bike.php', { method: 'POST', body: form });
        const data = await res.json();
        if (!data.success) { alert('Restore failed.'); return; }
        await loadBikes();
        alert('Bike restored to Available.');
    } catch (e) {
        console.error(e);
        alert('Network error while restoring bike');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadBikes();
    setupAddImageUpload();

    const addForm = document.getElementById('addBikeForm');
    if (addForm) addForm.addEventListener('submit', handleAddBikeSubmit);

    const editForm = document.getElementById('editBikeForm');
    if (editForm) editForm.addEventListener('submit', handleEditBikeSubmit);

    window.addEventListener('click', (e) => {
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        if (e.target === addModal) closeAddModal();
        if (e.target === editModal) closeEditModal();
        if (e.target === deleteModal) closeDeleteModal();
    });

    document.getElementById('statusFilter').addEventListener('change', filterBikes);
    document.getElementById('typeFilter').addEventListener('change', filterBikes);
    document.getElementById('priceFilter').addEventListener('change', filterBikes);
    document.getElementById('searchInput').addEventListener('input', filterBikes);
});