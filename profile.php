<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.html');
    exit();
}

// If the logged-in user is an admin, redirect them to Settings
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: settings.html');
    exit();
}

// Database configuration
$serverName = "localhost";
$database = "BikeRental";
$username = "";
$password = "";

$connectionOptions = array(
    "Database" => $database,
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Database connection failed");
}

// Get user data based on type
$userData = null;
$userType = $_SESSION['user_type'];

if ($userType === 'admin') {
    // Get admin data
    $sql = "EXEC sp_GetAdminProfile @AdminID = ?";
    $params = array($_SESSION['user_id']);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $userData = [
            'id' => $admin['Admin_ID'],
            'username' => $admin['username'],
            'name' => $admin['full_name'],
            'email' => $admin['username'], // Admins use username as email
            'role' => $admin['role'],
            'type' => 'admin',
            'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop',
            'badge' => $admin['role'],
            'join_date' => 'Admin since 2024'
        ];
    }
} else {
    // Get member data
    $sql = "EXEC sp_GetMemberProfile @MemberID = ?";
    $params = array($_SESSION['user_id']);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && $member = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $userData = [
            'id' => $member['Member_ID'],
            'username' => $member['username'],
            'name' => $member['first_name'] . ' ' . $member['last_name'],
            'email' => $member['email'],
            'phone' => $member['contact_number'],
            'address' => $member['address'],
            'type' => 'member',
            'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop',
            'badge' => 'Gold Member',
            'join_date' => 'Member since ' . date('F Y', strtotime($member['date_joined']->format('Y-m-d')))
        ];
    }
}

if (!$userData) {
    // User not found, logout
    session_destroy();
    header('Location: login.html');
    exit();
}

sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BikeRental - <?php echo ucfirst($userData['type']); ?> Profile</title>
    <link rel="stylesheet" href="ahome.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="10" cy="22" r="4" stroke="#22d3ee" stroke-width="2.5"/>
                    <circle cx="22" cy="22" r="4" stroke="#22d3ee" stroke-width="2.5"/>
                    <path d="M16 8L10 22M16 8L22 22" stroke="#22d3ee" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="10" y1="22" x2="22" y2="22" stroke="#22d3ee" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="logo-text">BikeRental</span>
            </div>
            <ul class="nav-menu">
                <?php if ($userData['type'] === 'admin'): ?>
                    <li><a href="dashboard.html">Dashboard</a></li>
                    <li><a href="customers.html">Customers</a></li>
                    <li><a href="rentals.html">Rentals</a></li>
                    <li><a href="1.html">Bikes</a></li>
                <?php else: ?>
                    <li><a href="user_home.html">Home</a></li>
                    <li><a href="2browse.html">Browse Bikes</a></li>
                    <li><a href="ahowitworks.html">How It Works</a></li>
                    <li><a href="apricing.html">Pricing</a></li>
                    <li><a href="contact.html">Contact</a></li>
                    <li><a href="my_rental.html">My Rentals</a></li>
                <?php endif; ?>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><button id="logout-btn" class="logout-btn">Logout</button></li>
            </ul>
        </div>
    </nav>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="profile-header-container">
            <div class="profile-avatar-wrapper">
                <img src="<?php echo $userData['avatar']; ?>" alt="Profile Picture" class="profile-avatar">
                <div class="avatar-edit-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 13L9 17L19 7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($userData['name']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($userData['email']); ?></p>
                <div class="profile-badges">
                    <span class="badge badge-primary"><?php echo htmlspecialchars($userData['badge']); ?></span>
                    <span class="badge badge-secondary"><?php echo htmlspecialchars($userData['join_date']); ?></span>
                    <?php if ($userData['type'] === 'admin'): ?>
                        <span class="badge badge-admin">Administrator</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="profile-content">
        <div class="container">
            <div class="profile-grid">
                <!-- Personal Information -->
                <div class="profile-card">
                    <div class="card-header">
                        <h3>Personal Information</h3>
                        <button class="edit-btn" onclick="openPersonalModal()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="info-row">
                            <span class="label">Full Name:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Username:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['username']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Email:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['email']); ?></span>
                        </div>
                        <?php if ($userData['type'] === 'member' && isset($userData['phone'])): ?>
                        <div class="info-row">
                            <span class="label">Phone:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($userData['type'] === 'member' && isset($userData['address'])): ?>
                        <div class="info-row">
                            <span class="label">Address:</span>
                            <span class="value"><?php echo htmlspecialchars($userData['address'] ?? 'Not provided'); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="label">Account Type:</span>
                            <span class="value"><?php echo ucfirst($userData['type']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="profile-card">
                    <div class="card-header">
                        <h3>Account Statistics</h3>
                    </div>
                    <div class="card-content">
                        <?php if ($userData['type'] === 'admin'): ?>
                            <!-- Admin Statistics -->
                            <div class="stat-row">
                                <span class="stat-label">Total Bikes:</span>
                                <span class="stat-value" id="total-bikes">Loading...</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Active Rentals:</span>
                                <span class="stat-value" id="active-rentals">Loading...</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Total Members:</span>
                                <span class="stat-value" id="total-members">Loading...</span>
                            </div>
                            <!-- Admin Actions -->
                            <div class="admin-actions">
                                <button id="profile-logout-btn" class="logout-btn-secondary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <polyline points="16,17 21,12 16,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Logout
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Member Statistics -->
                            <div class="stat-row">
                                <span class="stat-label">Total Rentals:</span>
                                <span class="stat-value" id="total-rentals">Loading...</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Active Rentals:</span>
                                <span class="stat-value" id="active-rentals">Loading...</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Favorite Bikes:</span>
                                <span class="stat-value" id="favorite-bikes">0</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="profile-card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="card-content">
                        <div class="activity-list" id="recent-activity">
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="activity-content">
                                    <p>Profile updated successfully</p>
                                    <span class="activity-time">2 hours ago</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="activity-content">
                                    <p>Account created</p>
                                    <span class="activity-time"><?php echo $userData['join_date']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modals for editing -->
    <div id="personalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Personal Information</h3>
                <span class="close" onclick="closePersonalModal()">&times;</span>
            </div>
            <form id="personalForm">
                <div class="form-group">
                    <label for="editName">Full Name</label>
                    <input type="text" id="editName" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>
                <?php if ($userData['type'] === 'member'): ?>
                <div class="form-group">
                    <label for="editPhone">Phone</label>
                    <input type="tel" id="editPhone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="editAddress">Address</label>
                    <textarea id="editAddress"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                </div>
                <?php endif; ?>
                <div class="modal-actions">
                    <button type="button" onclick="closePersonalModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="logout.js"></script>
    <script>
        // Load statistics based on user type
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($userData['type'] === 'admin'): ?>
                loadAdminStats();
            <?php else: ?>
                loadMemberStats();
            <?php endif; ?>

            // Add event listener for profile logout button
            const profileLogoutBtn = document.getElementById('profile-logout-btn');
            if (profileLogoutBtn) {
                profileLogoutBtn.addEventListener('click', async function(e) {
                    e.preventDefault();

                    // Show confirmation dialog
                    const confirmLogout = confirm('Are you sure you want to logout?');
                    if (!confirmLogout) {
                        return;
                    }

                    try {
                        // Call logout endpoint
                        const response = await fetch('logout.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Clear localStorage
                            localStorage.removeItem('userId');
                            localStorage.removeItem('userEmail');
                            localStorage.removeItem('userName');
                            localStorage.removeItem('userType');
                            localStorage.removeItem('loginTime');

                            // Show success message
                            alert('Logged out successfully!');

                            // Redirect to login page
                            window.location.href = 'login.html';
                        } else {
                            alert('Logout failed. Please try again.');
                        }
                    } catch (error) {
                        console.error('Logout error:', error);
                        // Even if the server call fails, clear local data and redirect
                        localStorage.clear();
                        alert('Logged out locally. Redirecting to login...');
                        window.location.href = 'login.html';
                    }
                });
            }
        });

        <?php if ($userData['type'] === 'admin'): ?>
        function loadAdminStats() {
            // Load admin statistics
            fetch('get_admin_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-bikes').textContent = data.totalBikes;
                        document.getElementById('active-rentals').textContent = data.activeRentals;
                        document.getElementById('total-members').textContent = data.totalMembers;
                    }
                })
                .catch(error => console.error('Error loading admin stats:', error));
        }
        <?php else: ?>
        function loadMemberStats() {
            // Load member statistics
            fetch('get_member_stats.php?member_id=<?php echo $userData['id']; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-rentals').textContent = data.totalRentals;
                        document.getElementById('active-rentals').textContent = data.activeRentals;
                        document.getElementById('favorite-bikes').textContent = data.favoriteBikes;
                    }
                })
                .catch(error => console.error('Error loading member stats:', error));
        }
        <?php endif; ?>

        // Modal functions
        function openPersonalModal() {
            document.getElementById('personalModal').style.display = 'block';
        }

        function closePersonalModal() {
            document.getElementById('personalModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('personalModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>