// Shared Bike Database - Used by both admin and user pages
// This file manages bike data in memory (simulating a database)

// Initialize bikes from default data or memory
function initializeBikes() {
    const defaultBikes = {
        city: [
            { id: "B101", name: "City Cruiser", speed: "20 km/h", weight: "12 kg", gears: "7 gears", image: "https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=500", price: 50, condition: "excellent", status: "available" },
            { id: "B102", name: "Urban Commuter", speed: "22 km/h", weight: "11 kg", gears: "8 gears", image: "https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?w=500", price: 50, condition: "excellent", status: "available" },
            { id: "B103", name: "Metro Glide", speed: "21 km/h", weight: "13 kg", gears: "6 gears", image: "https://www.rideandglide.co.uk/wp-content/uploads/evolt-folding-electric-bike-cream_0015_Layer-2.jpg", price: 50, condition: "good", status: "available" },
            { id: "B104", name: "Downtown Rider", speed: "19 km/h", weight: "12 kg", gears: "7 gears", image: "https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=500", price: 50, condition: "excellent", status: "available" },
            { id: "B105", name: "Street Smart", speed: "23 km/h", weight: "10 kg", gears: "8 gears", image: "https://i.pinimg.com/1200x/cb/3f/60/cb3f60fe070c14ac591b490c325cf71e.jpg", price: 50, condition: "excellent", status: "rented" },
            { id: "B106", name: "Campus Cruiser", speed: "18 km/h", weight: "13 kg", gears: "3 gears", image: "https://i.pinimg.com/736x/49/e8/62/49e862a74247c408887a1f7016b56613.jpg", price: 50, condition: "good", status: "available" },
            { id: "B107", name: "City Express", speed: "24 km/h", weight: "11 kg", gears: "7 gears", image: "https://i.pinimg.com/1200x/0a/6b/ae/0a6baeab6593163e6aaa53860694bf97.jpg", price: 50, condition: "excellent", status: "available" },
            { id: "B108", name: "Daily Ride", speed: "20 km/h", weight: "12 kg", gears: "6 gears", image: "https://i.pinimg.com/1200x/ba/61/b3/ba61b302bc096b6eac8f45a5291b9a78.jpg", price: 50, condition: "good", status: "available" },
            { id: "B109", name: "Commuter Pro", speed: "22 km/h", weight: "10 kg", gears: "8 gears", image: "https://i.pinimg.com/736x/05/71/82/0571822417f7a48541ce594cb4b0b04b.jpg", price: 50, condition: "excellent", status: "available" },
            { id: "B110", name: "Urban Swift", speed: "21 km/h", weight: "11 kg", gears: "7 gears", image: "https://i.pinimg.com/1200x/97/d9/97/97d997d2f28509dd8e5f3ce9b77c2b99.jpg", price: 50, condition: "excellent", status: "available" },
            { id: "B111", name: "City Navigator", speed: "19 km/h", weight: "12 kg", gears: "8 gears", image: "https://i.pinimg.com/736x/9f/3a/d6/9f3ad67539e2a98de70562e17f51dd23.jpg", price: 50, condition: "good", status: "available" },
            { id: "B112", name: "Metro Classic", speed: "20 km/h", weight: "13 kg", gears: "6 gears", image: "https://images.unsplash.com/photo-1576435728678-68d0fbf94e91?w=500", price: 50, condition: "excellent", status: "available" }
        ],
        mountain: [
            { id: "B201", name: "Trail Blazer", speed: "25 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/60/54/dc/6054dc77ac6d680a79213010d0de59f6.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B202", name: "Mountain King", speed: "27 km/h", weight: "14 kg", gears: "24 gears", image: "https://i.pinimg.com/736x/ae/c4/63/aec4638c759b2b13998574b22cdd7484.jpg", price: 100, condition: "excellent", status: "rented" },
            { id: "B203", name: "Peak Rider", speed: "26 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/66/10/42/6610424e61e6ec7cd6cbd8272534d6a6.jpg", price: 100, condition: "good", status: "available" },
            { id: "B204", name: "Summit Pro", speed: "28 km/h", weight: "13 kg", gears: "27 gears", image: "https://i.pinimg.com/736x/e6/c2/28/e6c2284c39f1e83e3bb80ca6ba17b50a.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B205", name: "Rocky Terrain", speed: "24 km/h", weight: "16 kg", gears: "18 gears", image: "https://i.pinimg.com/1200x/9e/e2/7e/9ee27e4f26bfcddafd6b6a1ea5319af9.jpg", price: 100, condition: "good", status: "available" },
            { id: "B206", name: "Trail Master", speed: "27 km/h", weight: "14 kg", gears: "24 gears", image: "https://images.unsplash.com/photo-1559348349-86f1f65817fe?w=500", price: 100, condition: "excellent", status: "available" },
            { id: "B207", name: "Off-Road Beast", speed: "25 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/48/fe/a6/48fea660cd996bfbfda6970ca7134cd3.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B208", name: "Adventure Seeker", speed: "26 km/h", weight: "14 kg", gears: "22 gears", image: "https://i.pinimg.com/1200x/0b/01/d5/0b01d5ec3cfd515af98e74a772fcb294.jpg", price: 100, condition: "good", status: "available" },
            { id: "B209", name: "Mountain Explorer", speed: "28 km/h", weight: "13 kg", gears: "27 gears", image: "https://i.pinimg.com/736x/71/05/3c/71053c39cabc2ec0de8b3c004fe230da.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B210", name: "Ridge Runner", speed: "25 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/9a/6b/6b/9a6b6b203eeba063ec1ddedae2f47e81.jpg", price: 100, condition: "excellent", status: "rented" },
            { id: "B211", name: "Alpine Climber", speed: "27 km/h", weight: "14 kg", gears: "24 gears", image: "https://i.pinimg.com/1200x/e3/0d/99/e30d994faaa25f734024f6a7d2436a2a.jpg", price: 100, condition: "good", status: "available" },
            { id: "B212", name: "Trail Dominator", speed: "29 km/h", weight: "13 kg", gears: "27 gears", image: "http://i.pinimg.com/1200x/2b/c7/14/2bc71427198c2dc7b0a99759f21fa736.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B213", name: "Wilderness Rider", speed: "26 km/h", weight: "15 kg", gears: "21 gears", image: "https://i.pinimg.com/1200x/2b/e6/1b/2be61b8ca96597dbda22318f729fbdf6.jpg", price: 100, condition: "good", status: "available" }
        ],
        electric: [
            { id: "B301", name: "E-Speed Pro", speed: "35 km/h", weight: "22 kg", gears: "50km range", image: "https://i.pinimg.com/1200x/e5/a7/b6/e5a7b6628d43fabeb58852f87abac9c1.jpg", price: 150, condition: "excellent", status: "available" },
            { id: "B302", name: "Power Glide", speed: "40 km/h", weight: "24 kg", gears: "60km range", image: "https://i.pinimg.com/1200x/2c/3a/9a/2c3a9aadc24dabd4a36452df08393e25.jpg", price: 150, condition: "excellent", status: "available" },
            { id: "B303", name: "Volt Cruiser", speed: "38 km/h", weight: "23 kg", gears: "55km range", image: "https://i.pinimg.com/1200x/6f/03/af/6f03afe6ccc84b4bfc7dc357e7dcc083.jpg", price: 150, condition: "excellent", status: "rented" },
            { id: "B304", name: "Thunder Bolt", speed: "42 km/h", weight: "25 kg", gears: "70km range", image: "https://i.pinimg.com/736x/95/9b/d7/959bd74d38e549feb2b8ffe0c26c9100.jpg", price: 150, condition: "excellent", status: "available" },
            { id: "B305", name: "E-Commute Max", speed: "36 km/h", weight: "22 kg", gears: "50km range", image: "https://i.pinimg.com/1200x/c0/48/85/c04885546df63f007a3e9d1f07902594.jpg", price: 150, condition: "good", status: "available" },
            { id: "B306", name: "Eco Rider", speed: "33 km/h", weight: "21 kg", gears: "45km range", image: "https://i.pinimg.com/736x/d0/ee/f7/d0eef7a28d32b9f23c396823f8f9a119.jpg", price: 150, condition: "excellent", status: "available" },
            { id: "B307", name: "Electric Wave", speed: "39 km/h", weight: "24 kg", gears: "65km range", image: "https://i.pinimg.com/736x/1f/53/a9/1f53a9b144e06d806716b17f40b152f5.jpg", price: 150, condition: "excellent", status: "available" },
            { id: "B308", name: "City Volt", speed: "35 km/h", weight: "22 kg", gears: "50km range", image: "https://i.pinimg.com/736x/9f/90/ad/9f90ad9f36ee8c171af87619a496fc01.jpg", price: 150, condition: "good", status: "available" },
            { id: "B309", name: "E-Motion", speed: "37 km/h", weight: "23 kg", gears: "55km range", image: "https://i.pinimg.com/1200x/ce/57/73/ce57737a8e0d9feb7ef77dbd78e8eecb.jpg", price: 150, condition: "excellent", status: "available" },
            { id: "B310", name: "Smart Ride", speed: "38 km/h", weight: "24 kg", gears: "60km range", image: "https://i.pinimg.com/736x/5c/da/8c/5cda8cff06f975b056ad3436c9388b88.jpg", price: 150, condition: "excellent", status: "available" },
            { id: "B311", name: "Turbo E-Bike", speed: "41 km/h", weight: "25 kg", gears: "68km range", image: "https://i.pinimg.com/1200x/59/6f/96/596f96f1b8a6144705820b28f3efdd3b.jpg", price: 150, condition: "good", status: "available" },
            { id: "B312", name: "Hyper Volt", speed: "43 km/h", weight: "26 kg", gears: "75km range", image: "https://i.pinimg.com/1200x/73/eb/c1/73ebc1a157967a43e0f58290de4b4c3f.jpg", price: 150, condition: "excellent", status: "rented" },
            { id: "B313", name: "Urban E-Cruiser", speed: "36 km/h", weight: "23 kg", gears: "52km range", image: "https://i.pinimg.com/736x/7f/12/1e/7f121e781186dab5f5ea2baed280ccac.jpg", price: 150, condition: "excellent", status: "available" }
        ],
        kids: [
            { id: "B401", name: "Junior Explorer", speed: "15 km/h", weight: "8 kg", gears: "1 gear", image: "https://i.pinimg.com/736x/ea/bc/2d/eabc2d40c865cb5481a066ec760995c7.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B402", name: "Little Rider", speed: "12 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/77/88/86/77888639573347b93cc671f9a6bf8091.jpg", price: 100, condition: "good", status: "available" },
            { id: "B403", name: "Mini Cruiser", speed: "14 km/h", weight: "8 kg", gears: "3 gears", image: "https://i.pinimg.com/1200x/c8/b4/a0/c8b4a05ca779c8527b9a5084830dc44d.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B404", name: "Tiny Wheels", speed: "10 km/h", weight: "6 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/30/88/08/3088089be34df817a6033182e542d48f.jpg", price: 100, condition: "good", status: "available" },
            { id: "B405", name: "Young Adventure", speed: "16 km/h", weight: "9 kg", gears: "3 gears", image: "https://i.pinimg.com/1200x/9e/f2/12/9ef21200d1ae5c5cf11e07f9b6bfbafc.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B406", name: "Kids Champion", speed: "13 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/736x/d4/e5/90/d4e5908e78be134e2b8bfb3fc330ea0d.jpg", price: 100, condition: "excellent", status: "rented" },
            { id: "B407", name: "Safe Rider", speed: "15 km/h", weight: "8 kg", gears: "3 gears", image: "https://i.pinimg.com/736x/49/b2/07/49b20721d72865f7eb6e8f4a1f658f70.jpg", price: 100, condition: "good", status: "available" },
            { id: "B408", name: "Starter Bike", speed: "11 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/34/54/8f/34548f3fc66c1a666cb8183a7f3071e3.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B409", name: "Youth Cruiser", speed: "14 km/h", weight: "8 kg", gears: "3 gears", image: "https://i.pinimg.com/1200x/d8/63/c0/d863c0dd7c3036616d1fe7610ad17c38.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B410", name: "Junior Pro", speed: "16 km/h", weight: "9 kg", gears: "6 gears", image: "https://i.pinimg.com/1200x/7d/8d/b5/7d8db5181877918672854467413bbbb4.jpg", price: 100, condition: "good", status: "available" },
            { id: "B411", name: "Mini Mountain", speed: "17 km/h", weight: "10 kg", gears: "7 gears", image: "https://i.pinimg.com/1200x/42/f6/59/42f659654090aaccc17baa9759d55822.jpg", price: 100, condition: "excellent", status: "available" },
            { id: "B412", name: "First Adventure", speed: "12 km/h", weight: "7 kg", gears: "1 gear", image: "https://i.pinimg.com/1200x/85/09/95/8509957ff9ba2b04781c498652fb735d.jpg", price: 100, condition: "good", status: "available" }
        ],
        premium: [
            { id: "B501", name: "Carbon Elite", speed: "45 km/h", weight: "8 kg", gears: "22 gears", image: "https://i.pinimg.com/1200x/0c/1a/a9/0c1aa91b41fbdacf06b6ec6bd7fb2e96.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B502", name: "Titanium Racer", speed: "48 km/h", weight: "7 kg", gears: "24 gears", image: "https://i.pinimg.com/1200x/12/29/e0/1229e035dcc48499928e87a768ccefd2.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B503", name: "Diamond Series", speed: "46 km/h", weight: "8 kg", gears: "22 gears", image: "https://i.pinimg.com/736x/60/62/b9/6062b985dc94c45318b5c48f68c451be.jpg", price: 200, condition: "excellent", status: "rented" },
            { id: "B504", name: "Platinum Ride", speed: "47 km/h", weight: "7 kg", gears: "24 gears", image: "https://i.pinimg.com/736x/61/1b/98/611b9839836745424d24c2ce55bc584e.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B505", name: "Gold Edition", speed: "49 km/h", weight: "8 kg", gears: "27 gears", image: "https://i.pinimg.com/1200x/23/2f/3c/232f3c9414897e88965519b013005a58.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B506", name: "Luxury Cruiser", speed: "44 km/h", weight: "9 kg", gears: "22 gears", image: "https://i.pinimg.com/1200x/d0/47/e2/d047e2367be66c20647fd92b674e10ae.jpg", price: 200, condition: "good", status: "available" },
            { id: "B507", name: "Elite Performer", speed: "50 km/h", weight: "7 kg", gears: "30 gears", image: "https://i.pinimg.com/736x/a3/20/98/a320989decb47853edda580b02184644.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B508", name: "Professional Grade", speed: "47 km/h", weight: "8 kg", gears: "24 gears", image: "https://i.pinimg.com/736x/ef/3a/d7/ef3ad7bf2132ef07652212b4aeedd0fc.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B509", name: "Championship", speed: "48 km/h", weight: "7 kg", gears: "27 gears", image: "https://i.pinimg.com/1200x/5c/3a/90/5c3a909dfdb61c0e29866d65430081c6.jpg", price: 200, condition: "good", status: "available" },
            { id: "B510", name: "Ultimate Speed", speed: "51 km/h", weight: "7 kg", gears: "30 gears", image: "https://i.pinimg.com/1200x/09/22/c6/0922c6f3ac9c5c86807c2f806b8267b3.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B511", name: "Master Class", speed: "46 km/h", weight: "8 kg", gears: "24 gears", image: "https://i.pinimg.com/1200x/4b/5a/4b/4b5a4b9fe58bd0a30bdb6ed1d9e76ffb.jpg", price: 200, condition: "excellent", status: "available" },
            { id: "B512", name: "Supreme Racer", speed: "49 km/h", weight: "7 kg", gears: "27 gears", image: "https://i.pinimg.com/1200x/d3/be/c8/d3bec8981503f6b2e84d7a8e9ca6529f.jpg", price: 200, condition: "good", status: "rented" },
            { id: "B513", name: "Legend Series", speed: "52 km/h", weight: "7 kg", gears: "30 gears", image: "https://i.pinimg.com/1200x/d2/54/fe/d254fec60d81fc0ac46c0959036e6934.jpg", price: 200, condition: "excellent", status: "available" }
        ]
    };

    // Check if bikes exist in memory, if not initialize with defaults
    if (!window.bikesDatabase) {
        const savedBikes = localStorage.getItem('bikesDatabase');
        if (savedBikes) {
            try {
                window.bikesDatabase = JSON.parse(savedBikes);
                console.log('✅ Bikes loaded from localStorage:', Object.keys(window.bikesDatabase).length + ' categories');
            } catch (error) {
                console.error('❌ Error parsing saved bikes, using defaults');
                window.bikesDatabase = defaultBikes;
                saveBikesToSession();
            }
        } else {
            console.log('ℹ️ No saved bikes found, using default bikes');
            window.bikesDatabase = defaultBikes;
            saveBikesToSession();
        }
    }
}

// Save bikes to local storage (persists across sessions)
function saveBikesToSession() {
    try {
        localStorage.setItem('bikesDatabase', JSON.stringify(window.bikesDatabase));
        console.log('✅ Bikes saved to localStorage');
    } catch (error) {
        console.error('❌ Error saving bikes:', error);
    }
}

// Get all bikes as a flat array
function getBikesArray() {
    initializeBikes();
    const bikesArray = [];
    for (const [category, bikeList] of Object.entries(window.bikesDatabase)) {
        bikeList.forEach(bike => {
            bikesArray.push({ ...bike, category });
        });
    }
    return bikesArray;
}

// Get bikes by category
function getBikesByCategory(category) {
    initializeBikes();
    return window.bikesDatabase[category] || [];
}

// Add a new bike
function addBike(bikeData) {
    initializeBikes();
    const category = bikeData.category;
    
    if (!window.bikesDatabase[category]) {
        window.bikesDatabase[category] = [];
    }
    
    window.bikesDatabase[category].push(bikeData);
    saveBikesToSession();
    console.log('✅ Bike added:', bikeData.name, '(ID:', bikeData.id + ')');
    return true;
}

// Update a bike
function updateBike(bikeId, updatedData) {
    initializeBikes();
    
    for (const category in window.bikesDatabase) {
        const bikeIndex = window.bikesDatabase[category].findIndex(b => b.id === bikeId);
        if (bikeIndex !== -1) {
            window.bikesDatabase[category][bikeIndex] = {
                ...window.bikesDatabase[category][bikeIndex],
                ...updatedData
            };
            saveBikesToSession();
            return true;
        }
    }
    return false;
}

// Delete a bike
function deleteBike(bikeId) {
    initializeBikes();
    
    for (const category in window.bikesDatabase) {
        const bikeIndex = window.bikesDatabase[category].findIndex(b => b.id === bikeId);
        if (bikeIndex !== -1) {
            window.bikesDatabase[category].splice(bikeIndex, 1);
            saveBikesToSession();
            return true;
        }
    }
    return false;
}

// Get a single bike by ID
function getBikeById(bikeId) {
    initializeBikes();
    
    for (const category in window.bikesDatabase) {
        const bike = window.bikesDatabase[category].find(b => b.id === bikeId);
        if (bike) {
            return { ...bike, category };
        }
    }
    return null;
}

// Initialize on script load
initializeBikes();