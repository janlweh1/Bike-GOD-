// Logout functionality
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logout-btn');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function(e) {
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

    // Check if user is logged in when page loads
    checkLoginStatus();
});

// Function to check if user is logged in
function checkLoginStatus() {
    const userId = localStorage.getItem('userId');
    const userType = localStorage.getItem('userType');

    if (!userId || !userType) {
        // User not logged in, redirect to login
        alert('Please log in to access this page.');
        window.location.href = 'login.html';
        return;
    }

    // Optional: Check session validity with server
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.loggedIn) {
                // Session expired, clear local data and redirect
                localStorage.clear();
                alert('Your session has expired. Please log in again.');
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('Session check error:', error);
            // Continue normally if session check fails
        });
}