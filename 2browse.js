// Live Browse Bikes (customer) — pulls data from backend to match admin
// Depends on DOM in 2browse.html and the existing CSS classes

(function () {
  const API_URL = 'get_bikes.php';
  const gridEl = document.getElementById('bikesGrid');
  const tabs = document.querySelectorAll('.tab-btn');

  let allBikes = [];
  let selectedBike = null;
  let currentPrice = 0;

  function isHttpUrl(url) {
    return /^https?:\/\//i.test(url);
  }

  function normalizeCategory(raw) {
    if (!raw) return 'other';
    const v = String(raw).trim().toLowerCase();
    // map common variants
    if (v.startsWith('city')) return 'city';
    if (v.startsWith('mount')) return 'mountain';
    if (v.startsWith('elec')) return 'electric';
    if (v.startsWith('kid')) return 'kids';
    if (v.startsWith('prem')) return 'premium';
    return v || 'other';
  }

  function normalizeAvailability(raw) {
    if (!raw) return 'available';
    const v = String(raw).trim().toLowerCase();
    if (['available', 'avail', 'yes', 'true', '1'].includes(v)) return 'available';
    if (['unavailable', 'rented', 'no', 'false', '0', 'maintenance'].includes(v)) return 'unavailable';
    return v;
  }

  function toPeso(n) {
    const num = Number(n) || 0;
    return '₱' + num.toFixed(2);
  }

  function resolveImage(photoUrl) {
    if (photoUrl && (isHttpUrl(photoUrl) || photoUrl.startsWith('uploads/'))) {
      return photoUrl;
    }
    // final fallback placeholder available in repo
    return 'picture.avif';
  }

  async function fetchBikes() {
    try {
      const res = await fetch(API_URL, { cache: 'no-store' });
      const data = await res.json();
      if (!data || data.success !== true || !Array.isArray(data.bikes)) {
        throw new Error('Invalid bikes payload');
      }
      return data.bikes.map((b) => ({
        id: b.id,
        name: b.model || ('Bike #' + b.id),
        category: normalizeCategory(b.type),
        price: Number(b.hourly_rate) || 0,
        image: resolveImage(b.photo_url),
        status: normalizeAvailability(b.availability),
        condition: b.condition || 'Excellent'
      }));
    } catch (e) {
      console.error('Failed to load bikes:', e);
      return [];
    }
  }

  function createBikeCard(bike) {
    const available = bike.status !== 'unavailable';
    const badgeClass = available ? 'available' : 'unavailable';
    const badgeText = available ? 'Available' : 'Unavailable';
    const disabledAttr = available ? '' : 'disabled aria-disabled="true"';
    const disabledClass = available ? '' : ' disabled';
    return (
      '<div class="bike-card" data-category="' + bike.category + '">' +
        '<div class="bike-image">' +
          '<img src="' + bike.image + '" alt="' + bike.name.replace(/"/g, '&quot;') + '">' +
          '<span class="availability-badge ' + badgeClass + '">' + badgeText + '</span>' +
        '</div>' +
        '<div class="bike-info">' +
          '<h3>' + bike.name + '</h3>' +
          '<p class="bike-type">' + (bike.category.charAt(0).toUpperCase() + bike.category.slice(1)) + ' Bike</p>' +
          '<div class="bike-specs">' +
            '<span><svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2L10 6L14 6.5L11 9.5L11.5 14L8 12L4.5 14L5 9.5L2 6.5L6 6L8 2Z"/></svg> ' + (bike.condition || 'Excellent') + '</span>' +
          '</div>' +
          '<div class="bike-footer">' +
            '<div class="price">' + toPeso(bike.price) + '<span>/hour</span></div>' +
            '<button class="rent-btn' + disabledClass + '" ' + disabledAttr + ' data-id="' + bike.id + '">Rent Now</button>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function renderBikes(bikes) {
    if (!bikes || bikes.length === 0) {
      gridEl.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#64748b; padding:2rem;">No bikes found.</div>';
      return;
    }
    gridEl.innerHTML = bikes.map(createBikeCard).join('');

    // Attach rent handlers
    gridEl.querySelectorAll('.rent-btn:not(.disabled)').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const bike = allBikes.find((b) => String(b.id) === String(id));
        if (bike) openRentalModal(bike);
      });
    });
  }

  function applyTabFilters(category) {
    const cards = gridEl.querySelectorAll('.bike-card');
    cards.forEach((card) => {
      if (category === 'all' || card.getAttribute('data-category') === category) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function initFilters() {
    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const cat = tab.dataset.category;
        tabs.forEach((t) => t.classList.remove('active'));
        tab.classList.add('active');
        applyTabFilters(cat);
      });
    });
  }

  // Modal logic
  function openRentalModal(bike) {
    selectedBike = bike;
    currentPrice = Number(bike.price) || 0;
    document.getElementById('modalBikeName').textContent = bike.name;
    document.getElementById('modalBikeCategory').textContent = bike.category.charAt(0).toUpperCase() + bike.category.slice(1) + ' Bike';
    document.getElementById('modalBikeImage').src = bike.image;
    document.getElementById('modalBikeImage').alt = bike.name;
    document.getElementById('hourlyRate').textContent = toPeso(currentPrice) + '/hour';
    document.getElementById('duration').value = 3;
    updateCalculations();
    document.getElementById('rentalModal').classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeRentalModal() {
    document.getElementById('rentalModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    ['fullName','email','phone','pickupDate','pickupTime'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
  }

  function updateCalculations() {
    const duration = parseInt(document.getElementById('duration').value, 10) || 0;
    const subtotal = currentPrice * duration;
    document.getElementById('durationDisplay').textContent = duration + ' hour(s)';
    document.getElementById('subtotal').textContent = toPeso(subtotal);
    document.getElementById('total').textContent = toPeso(subtotal);
  }

  async function confirmRental() {
    const fullName = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const duration = parseInt(document.getElementById('duration').value, 10) || 0;
    const pickupDate = document.getElementById('pickupDate').value;
    const pickupTime = document.getElementById('pickupTime').value;

    if (!selectedBike) {
      alert('No bike selected.');
      return;
    }
    if (!fullName || !email || !phone || !pickupDate || !pickupTime || duration < 1) {
      alert('Please fill in all required fields');
      return;
    }

    // Verify session: must be logged in as member
    try {
      const sessRes = await fetch('check_session.php', { credentials: 'include' });
      const sess = await sessRes.json();
      if (!sess || sess.loggedIn !== true || sess.userType !== 'member') {
        alert('Please log in as a member to create a rental.');
        return;
      }
    } catch (e) {
      // continue; backend will also enforce
    }

    // Attempt backend creation so admin sees the rental
    try {
      const form = new FormData();
      form.append('bike_id', String(selectedBike.id));
      form.append('duration_hours', String(duration));
      form.append('pickup_date', pickupDate);
      form.append('pickup_time', pickupTime);

      const res = await fetch('create_rental.php', {
        method: 'POST',
        body: form,
        credentials: 'include'
      });
      const data = await res.json();
      if (!data || data.success !== true) {
        const msg = (data && data.message) ? data.message : 'Failed to create rental';
        throw new Error(msg);
      }

      // Optionally mirror in localStorage for My Rentals UI
      const stored = localStorage.getItem('activeRentals');
      const arr = stored ? JSON.parse(stored) : [];
      arr.push({
        id: String(data.rental_id),
        bikeId: selectedBike.id,
        name: selectedBike.name,
        category: selectedBike.category,
        price: selectedBike.price,
        image: selectedBike.image,
        duration: duration,
        startTime: new Date().toISOString(),
        pickupDate,
        pickupTime,
        customerName: fullName,
        customerEmail: email,
        customerPhone: phone,
        location: 'Downtown Station A',
        cost: (Number(selectedBike.price) || 0) * duration
      });
      localStorage.setItem('activeRentals', JSON.stringify(arr));

      alert('Rental confirmed! Redirecting to My Rentals page...');
      closeRentalModal();
      // Refresh list to reflect bike availability change
      allBikes = await fetchBikes();
      renderBikes(allBikes);
      setTimeout(() => { window.location.href = 'my_rental.html'; }, 400);
    } catch (e) {
      console.error(e);
      alert(String(e && e.message ? e.message : 'Could not create rental'));
    }
  }

  function initModalControls() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const minDate = `${yyyy}-${mm}-${dd}`;
    const dateEl = document.getElementById('pickupDate');
    if (dateEl) dateEl.setAttribute('min', minDate);

    const decBtn = document.getElementById('decreaseBtn');
    const incBtn = document.getElementById('increaseBtn');
    const durEl = document.getElementById('duration');
    if (decBtn && durEl) {
      decBtn.addEventListener('click', () => {
        const v = parseInt(durEl.value, 10) || 1;
        if (v > 1) { durEl.value = v - 1; updateCalculations(); }
      });
    }
    if (incBtn && durEl) {
      incBtn.addEventListener('click', () => {
        const v = parseInt(durEl.value, 10) || 1;
        if (v < 24) { durEl.value = v + 1; updateCalculations(); }
      });
    }

    const closeBtn = document.getElementById('closeModal');
    if (closeBtn) closeBtn.addEventListener('click', closeRentalModal);
    const overlay = document.getElementById('rentalModal');
    if (overlay) overlay.addEventListener('click', (e) => { if (e.target === overlay) closeRentalModal(); });
    const confirmBtn = document.getElementById('confirmBtn');
    if (confirmBtn) confirmBtn.addEventListener('click', confirmRental);
  }

  async function init() {
    initFilters();
    initModalControls();
    allBikes = await fetchBikes();
    renderBikes(allBikes);
  }

  document.addEventListener('DOMContentLoaded', init);
})();
