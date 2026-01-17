// Bike Database
const bikes = {
    city: [
        { name: "City Cruiser", speed: "20 km/h", weight: "12 kg", gears: "7 gears", image: "https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=500", price: 50 },
        { name: "Urban Commuter", speed: "22 km/h", weight: "11 kg", gears: "8 gears", image: "https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?w=500", price: 50 },
        { name: "Metro Glide", speed: "21 km/h", weight: "13 kg", gears: "6 gears", image: "https://www.rideandglide.co.uk/wp-content/uploads/evolt-folding-electric-bike-cream_0015_Layer-2.jpg", price: 50 },
        { name: "Downtown Rider", speed: "19 km/h", weight: "12 kg", gears: "7 gears", image: "https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=500", price: 50 },
        { name: "Street Smart", speed: "23 km/h", weight: "10 kg", gears: "8 gears", image: "https://i.pinimg.com/1200x/cb/3f/60/cb3f60fe070c14ac591b490c325cf71e.jpg", price: 50 },
        { name: "Campus Cruiser", speed: "18 km/h", weight: "13 kg", gears: "3 gears", image: "https://i.pinimg.com/736x/49/e8/62/49e862a74247c408887a1f7016b56613.jpg", price: 50 },
        { name: "City Express", speed: "24 km/h", weight: "11 kg", gears: "7 gears", image: "https://i.pinimg.com/1200x/0a/6b/ae/0a6baeab6593163e6aaa53860694bf97.jpg", price: 50 },
        { name: "Daily Ride", speed: "20 km/h", weight: "12 kg", gears: "6 gears", image: "https://i.pinimg.com/1200x/ba/61/b3/ba61b302bc096b6eac8f45a5291b9a78.jpg", price: 50 },
        { name: "Commuter Pro", speed: "22 km/h", weight: "10 kg", gears: "8 gears", image: "https://i.pinimg.com/736x/05/71/82/0571822417f7a48541ce594cb4b0b04b.jpg", price: 50 },
        { name: "Urban Swift", speed: "21 km/h", weight: "11 kg", gears: "7 gears", image: "https://i.pinimg.com/1200x/97/d9/97/97d997d2f28509dd8e5f3ce9b77c2b99.jpg", price: 50 },
        { name: "City Navigator", speed: "19 km/h", weight: "12 kg", gears: "8 gears", image: "https://i.pinimg.com/736x/9f/3a/d6/9f3ad67539e2a98de70562e17f51dd23.jpg", price: 50 },
        { name: "Metro Classic", speed: "20 km/h", weight: "13 kg", gears: "6 gears", image: "https://images.unsplash.com/photo-1576435728678-68d0fbf94e91?w=500", price: 50 }
    ],
    mountain: [
        { name: "Trail Blazer", speed: "25 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/60/54/dc/6054dc77ac6d680a79213010d0de59f6.jpg", price: 100 },
        { name: "Mountain King", speed: "27 km/h", weight: "14 kg", gears: "24 gears", image: "https://i.pinimg.com/736x/ae/c4/63/aec4638c759b2b13998574b22cdd7484.jpg", price: 100 },
        { name: "Peak Rider", speed: "26 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/66/10/42/6610424e61e6ec7cd6cbd8272534d6a6.jpg", price: 100 },
        { name: "Summit Pro", speed: "28 km/h", weight: "13 kg", gears: "27 gears", image: "https://i.pinimg.com/736x/e6/c2/28/e6c2284c39f1e83e3bb80ca6ba17b50a.jpg", price: 100 },
        { name: "Rocky Terrain", speed: "24 km/h", weight: "16 kg", gears: "18 gears", image: "https://i.pinimg.com/1200x/9e/e2/7e/9ee27e4f26bfcddafd6b6a1ea5319af9.jpg", price: 100 },
        { name: "Trail Master", speed: "27 km/h", weight: "14 kg", gears: "24 gears", image: "https://images.unsplash.com/photo-1559348349-86f1f65817fe?w=500", price: 100 },
        { name: "Off-Road Beast", speed: "25 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/48/fe/a6/48fea660cd996bfbfda6970ca7134cd3.jpg", price: 100 },
        { name: "Adventure Seeker", speed: "26 km/h", weight: "14 kg", gears: "22 gears", image: "https://i.pinimg.com/1200x/0b/01/d5/0b01d5ec3cfd515af98e74a772fcb294.jpg", price: 100 },
        { name: "Mountain Explorer", speed: "28 km/h", weight: "13 kg", gears: "27 gears", image: "https://i.pinimg.com/736x/71/05/3c/71053c39cabc2ec0de8b3c004fe230da.jpg", price: 100 },
        { name: "Ridge Runner", speed: "25 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/9a/6b/6b/9a6b6b203eeba063ec1ddedae2f47e81.jpg", price: 100 },
        { name: "Alpine Climber", speed: "27 km/h", weight: "14 kg", gears: "24 gears", image: "https://i.pinimg.com/1200x/e3/0d/99/e30d994faaa25f734024f6a7d2436a2a.jpg", price: 100 },
        { name: "Trail Dominator", speed: "29 km/h", weight: "13 kg", gears: "27 gears", image: "http://i.pinimg.com/1200x/2b/c7/14/2bc71427198c2dc7b0a99759f21fa736.jpg", price: 100 },
        { name: "Wilderness Rider", speed: "26 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/2b/e6/1b/2be61b8ca96597dbda22318f729fbdf6.jpg", price: 100 }
    ],
    electric: [
        { name: "E-Speed Pro", speed: "35 km/h", weight: "22 kg", gears: "50km range", image: "https://i.pinimg.com/1200x/e5/a7/b6/e5a7b6628d43fabeb58852f87abac9c1.jpg", price: 150 },
        { name: "Power Glide", speed: "40 km/h", weight: "24 kg", gears: "60km range", image: "https://i.pinimg.com/1200x/2c/3a/9a/2c3a9aadc24dabd4a36452df08393e25.jpg", price: 150 },
        { name: "Volt Cruiser", speed: "38 km/h", weight: "23 kg", gears: "55km range", image: "https://i.pinimg.com/1200x/6f/03/af/6f03afe6ccc84b4bfc7dc357e7dcc083.jpg", price: 150 },
        { name: "Thunder Bolt", speed: "42 km/h", weight: "25 kg", gears: "70km range", image: "https://i.pinimg.com/736x/95/9b/d7/959bd74d38e549feb2b8ffe0c26c9100.jpg", price: 150 },
        { name: "E-Commute Max", speed: "36 km/h", weight: "22 kg", gears: "50km range", image: "https://i.pinimg.com/1200x/c0/48/85/c04885546df63f007a3e9d1f07902594.jpg", price: 150 },
        { name: "Eco Rider", speed: "33 km/h", weight: "21 kg", gears: "45km range", image: "https://i.pinimg.com/736x/d0/ee/f7/d0eef7a28d32b9f23c396823f8f9a119.jpg", price: 150 },
        { name: "Electric Wave", speed: "39 km/h", weight: "24 kg", gears: "65km range", image: "https://i.pinimg.com/736x/1f/53/a9/1f53a9b144e06d806716b17f40b152f5.jpg", price: 150 },
        { name: "City Volt", speed: "35 km/h", weight: "22 kg", gears: "50km range", image: "https://i.pinimg.com/736x/9f/90/ad/9f90ad9f36ee8c171af87619a496fc01.jpg", price: 150 },
        { name: "E-Motion", speed: "37 km/h", weight: "23 kg", gears: "55km range", image: "https://i.pinimg.com/1200x/ce/57/73/ce57737a8e0d9feb7ef77dbd78e8eecb.jpg", price: 150 },
        { name: "Smart Ride", speed: "38 km/h", weight: "24 kg", gears: "60km range", image: "https://i.pinimg.com/736x/5c/da/8c/5cda8cff06f975b056ad3436c9388b88.jpg", price: 150 },
        { name: "Turbo E-Bike", speed: "41 km/h", weight: "25 kg", gears: "68km range", image: "https://i.pinimg.com/1200x/59/6f/96/596f96f1b8a6144705820b28f3efdd3b.jpg", price: 150 },
        { name: "Hyper Volt", speed: "43 km/h", weight: "26 kg", gears: "75km range", image: "https://i.pinimg.com/1200x/73/eb/c1/73ebc1a157967a43e0f58290de4b4c3f.jpg", price: 150 },
        { name: "Urban E-Cruiser", speed: "36 km/h", weight: "23 kg", gears: "52km range", image: "https://i.pinimg.com/736x/7f/12/1e/7f121e781186dab5f5ea2baed280ccac.jpg", price: 150 }
    ],
    kids: [
        { name: "Junior Explorer", speed: "15 km/h", weight: "8 kg", gears: "1 gear", image: "https://i.pinimg.com/736x/ea/bc/2d/eabc2d40c865cb5481a066ec760995c7.jpg", price: 100 },
        { name: "Little Rider", speed: "12 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/77/88/86/77888639573347b93cc671f9a6bf8091.jpg", price: 100 },
        { name: "Mini Cruiser", speed: "14 km/h", weight: "8 kg", gears: "3 gears", image: "https://i.pinimg.com/1200x/c8/b4/a0/c8b4a05ca779c8527b9a5084830dc44d.jpg", price: 100 },
        { name: "Tiny Wheels", speed: "10 km/h", weight: "6 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/30/88/08/3088089be34df817a6033182e542d48f.jpg", price: 100 },
        { name: "Young Adventure", speed: "16 km/h", weight: "9 kg", gears: "3 gears", image: "https://i.pinimg.com/1200x/9e/f2/12/9ef21200d1ae5c5cf11e07f9b6bfbafc.jpg", price: 100 },
        { name: "Kids Champion", speed: "13 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/736x/d4/e5/90/d4e5908e78be134e2b8bfb3fc330ea0d.jpg", price: 100 },
        { name: "Safe Rider", speed: "15 km/h", weight: "8 kg", gears: "3 gears", image: "https://i.pinimg.com/736x/49/b2/07/49b20721d72865f7eb6e8f4a1f658f70.jpg", price: 100 },
        { name: "Starter Bike", speed: "11 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/34/54/8f/34548f3fc66c1a666cb8183a7f3071e3.jpg", price: 100 },
        { name: "Youth Cruiser", speed: "14 km/h", weight: "8 kg", gears: "3 gears", image: "https://i.pinimg.com/1200x/d8/63/c0/d863c0dd7c3036616d1fe7610ad17c38.jpg", price: 100 },
        { name: "Junior Pro", speed: "16 km/h", weight: "9 kg", gears: "6 gears", image: "https://i.pinimg.com/1200x/7d/8d/b5/7d8db5181877918672854467413bbbb4.jpg", price: 100 },
        { name: "Mini Mountain", speed: "17 km/h", weight: "10 kg", gears: "7 gears", image: "https://i.pinimg.com/1200x/42/f6/59/42f659654090aaccc17baa9759d55822.jpg", price: 100 },
        { name: "First Adventure", speed: "12 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/85/09/95/8509957ff9ba2b04781c498652fb735d.jpg", price: 100 }
    ],
    premium: [
        { name: "Carbon Elite", speed: "45 km/h", weight: "8 kg", gears: "22 gears", image: "https://i.pinimg.com/1200x/0c/1a/a9/0c1aa91b41fbdacf06b6ec6bd7fb2e96.jpg", price: 200 },
        { name: "Titanium Racer", speed: "48 km/h", weight: "7 kg", gears: "24 gears", image: "https://i.pinimg.com/1200x/12/29/e0/1229e035dcc48499928e87a768ccefd2.jpg", price: 200 },
        { name: "Diamond Series", speed: "46 km/h", weight: "8 kg", gears: "22 gears", image: "https://i.pinimg.com/736x/60/62/b9/6062b985dc94c45318b5c48f68c451be.jpg", price: 200 },
        { name: "Platinum Ride", speed: "47 km/h", weight: "7 kg", gears: "24 gears", image: "https://i.pinimg.com/736x/61/1b/98/611b9839836745424d24c2ce55bc584e.jpg", price: 200 },
        { name: "Gold Edition", speed: "49 km/h", weight: "8 kg", gears: "27 gears", image: "https://i.pinimg.com/1200x/23/2f/3c/232f3c9414897e88965519b013005a58.jpg", price: 200 },
        { name: "Luxury Cruiser", speed: "44 km/h", weight: "9 kg", gears: "22 gears", image: "https://i.pinimg.com/1200x/d0/47/e2/d047e2367be66c20647fd92b674e10ae.jpg", price: 200 },
        { name: "Elite Performer", speed: "50 km/h", weight: "7 kg", gears: "30 gears", image: "https://i.pinimg.com/736x/a3/20/98/a320989decb47853edda580b02184644.jpg", price: 200 },
        { name: "Professional Grade", speed: "47 km/h", weight: "8 kg", gears: "24 gears", image: "https://i.pinimg.com/736x/ef/3a/d7/ef3ad7bf2132ef07652212b4aeedd0fc.jpg", price: 200 },
        { name: "Championship", speed: "48 km/h", weight: "7 kg", gears: "27 gears", image: "https://i.pinimg.com/1200x/5c/3a/90/5c3a909dfdb61c0e29866d65430081c6.jpg", price: 200 },
        { name: "Ultimate Speed", speed: "51 km/h", weight: "7 kg", gears: "30 gears", image: "https://i.pinimg.com/1200x/09/22/c6/0922c6f3ac9c5c86807c2f806b8267b3.jpg", price: 200 },
        { name: "Master Class", speed: "46 km/h", weight: "8 kg", gears: "24 gears", image: "https://i.pinimg.com/1200x/4b/5a/4b/4b5a4b9fe58bd0a30bdb6ed1d9e76ffb.jpg", price: 200 },
        { name: "Supreme Racer", speed: "49 km/h", weight: "7 kg", gears: "27 gears", image: "https://i.pinimg.com/1200x/d3/be/c8/d3bec8981503f6b2e84d7a8e9ca6529f.jpg", price: 200 },
        { name: "Legend Series", speed: "52 km/h", weight: "7 kg", gears: "30 gears", image: "https://i.pinimg.com/1200x/d2/54/fe/d254fec60d81fc0ac46c0959036e6934.jpg", price: 200 }
    ]
};

// Store selected bike data
let selectedBike = null;
let currentPrice = 50;

// Generate bike card HTML
function createBikeCard(bike, category) {
    return `
        <div class="bike-card" data-category="${category}">
            <div class="bike-image">
                <img src="${bike.image}" alt="${bike.name}">
                <span class="availability-badge available">Available</span>
            </div>
            <div class="bike-info">
                <h3>${bike.name}</h3>
                <p class="bike-type">${category.charAt(0).toUpperCase() + category.slice(1)} Bike</p>
                <div class="bike-specs">
                    <span><svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2L10 6L14 6.5L11 9.5L11.5 14L8 12L4.5 14L5 9.5L2 6.5L6 6L8 2Z"/></svg> ${bike.speed}</span>
                    <span><svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2C4.5 2 2 4.5 2 8C2 11.5 4.5 14 8 14C11.5 14 14 11.5 14 8C14 4.5 11.5 2 8 2ZM8 12C5.8 12 4 10.2 4 8C4 5.8 5.8 4 8 4C10.2 4 12 5.8 12 8C12 10.2 10.2 12 8 12Z"/></svg> ${bike.weight}</span>
                    <span><svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2C4.5 2 2 4.5 2 8C2 11.5 4.5 14 8 14C11.5 14 14 11.5 14 8C14 4.5 11.5 2 8 2Z"/></svg> ${bike.gears}</span>
                </div>
                <div class="bike-footer">
                    <div class="price">₱${bike.price}<span>/hour</span></div>
                    <button class="rent-btn" onclick="openRentalModal('${bike.name}', '${category}', ${bike.price}, '${bike.image}')">Rent Now</button>
                </div>
            </div>
        </div>
    `;
}

// Render all bikes
function renderBikes() {
    const grid = document.getElementById('bikesGrid');
    let html = '';
    
    for (const [category, bikeList] of Object.entries(bikes)) {
        bikeList.forEach(bike => {
            html += createBikeCard(bike, category);
        });
    }
    
    grid.innerHTML = html;
}

// Filter functionality
function initFilters() {
    const tabs = document.querySelectorAll('.tab-btn');
    const bikeCards = document.querySelectorAll('.bike-card');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const category = tab.dataset.category;
            
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            bikeCards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// Open rental modal
function openRentalModal(bikeName, category, price, image) {
    selectedBike = { name: bikeName, category: category, price: price, image: image };
    currentPrice = price;
    
    // Update modal content
    document.getElementById('modalBikeName').textContent = bikeName;
    document.getElementById('modalBikeCategory').textContent = category.charAt(0).toUpperCase() + category.slice(1) + ' Bike';
    document.getElementById('modalBikeImage').src = image;
    document.getElementById('modalBikeImage').alt = bikeName;
    document.getElementById('hourlyRate').textContent = '₱' + price.toFixed(2) + '/hour';
    
    // Reset duration to 3
    document.getElementById('duration').value = 3;
    updateCalculations();
    
    // Show modal
    document.getElementById('rentalModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close rental modal
function closeRentalModal() {
    document.getElementById('rentalModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('fullName').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('pickupDate').value = '';
    document.getElementById('pickupTime').value = '';
}

// Update calculations
function updateCalculations() {
    const duration = parseInt(document.getElementById('duration').value);
    const subtotal = currentPrice * duration;
    const total = subtotal;

    document.getElementById('durationDisplay').textContent = duration + ' hour(s)';
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    renderBikes();
    initFilters();
    
    // Set minimum date to today
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayString = year + '-' + month + '-' + day;
    document.getElementById('pickupDate').setAttribute('min', todayString);
    
    // Duration control buttons
    document.getElementById('decreaseBtn').addEventListener('click', function() {
        const durationInput = document.getElementById('duration');
        const currentValue = parseInt(durationInput.value);
        if (currentValue > 1) {
            durationInput.value = currentValue - 1;
            updateCalculations();
        }
    });

    document.getElementById('increaseBtn').addEventListener('click', function() {
        const durationInput = document.getElementById('duration');
        const currentValue = parseInt(durationInput.value);
        if (currentValue < 24) {
            durationInput.value = currentValue + 1;
            updateCalculations();
        }
    });
    
    // Close modal button
    document.getElementById('closeModal').addEventListener('click', closeRentalModal);
    
    // Close modal when clicking outside
    document.getElementById('rentalModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRentalModal();
        }
    });
    
    // Confirm button
    document.getElementById('confirmBtn').addEventListener('click', function() {
        const fullName = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const duration = document.getElementById('duration').value;
        const pickupDate = document.getElementById('pickupDate').value;
        const pickupTime = document.getElementById('pickupTime').value;

        if (!fullName || !email || !phone || !pickupDate || !pickupTime) {
            alert('Please fill in all required fields');
            return;
        }

        alert('Rental confirmed! You will receive a confirmation email shortly.');
        closeRentalModal();
    });
});