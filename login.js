// Login functionality
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    const usernameEmailInput = document.querySelector('input[type="text"]');
    const passwordInput = document.querySelector('input[type="password"]');
    const loginBtn = document.querySelector('.login-btn');

    // Add IDs to inputs for easier access
    usernameEmailInput.id = 'username_email';
    passwordInput.id = 'password';

    loginBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const usernameEmail = usernameEmailInput.value.trim();
        const password = passwordInput.value.trim();

        // Validate inputs
        if (!usernameEmail || !password) {
            showMessage('Please fill in all fields', 'error');
            return;
        }

        // Basic validation for username/email
        if (usernameEmail.length < 3) {
            showMessage('Username or email must be at least 3 characters', 'error');
            return;
        }

        // Disable button and show loading state
        loginBtn.disabled = true;
        loginBtn.textContent = 'Logging in...';

        try {
            // Create form data
            const formData = new FormData();
            formData.append('email', usernameEmail); // 'email' field now accepts username or email
            formData.append('password', password);

            // Call PHP backend
            const response = await fetch('login_process.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Login successful
                showMessage('Login successful! Redirecting...', 'success');
                
                // Store user data in localStorage
                localStorage.setItem('userId', data.userId);
                localStorage.setItem('userEmail', data.email);
                localStorage.setItem('userName', data.name);
                localStorage.setItem('userType', data.userType); // 'admin' or 'member'
                localStorage.setItem('loginTime', new Date().toISOString());

                // Redirect based on user type
                setTimeout(() => {
                    if (data.userType === 'admin') {
                        // Redirect admins straight to the dashboard
                        window.location.href = 'dashboard.html';
                    } else {
                        // Members go to their home page (not profile)
                        window.location.href = 'user_home.html';
                    }
                }, 1500);
            } else {
                // Login failed
                showMessage(data.message || 'Invalid username, email or password', 'error');
                loginBtn.disabled = false;
                loginBtn.textContent = 'Log In';
            }
        } catch (error) {
            console.error('Login error:', error);
            showMessage('Connection error. Please try again.', 'error');
            loginBtn.disabled = false;
            loginBtn.textContent = 'Log In';
        }
    });

    // Allow Enter key to submit
    loginForm.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginBtn.click();
        }
    });
});

// Email validation (now optional since we accept usernames too)
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Show message to user
function showMessage(message, type) {
    // Remove existing messages
    const existingMsg = document.querySelector('.message-box');
    if (existingMsg) {
        existingMsg.remove();
    }

    // Create message element
    const messageBox = document.createElement('div');
    messageBox.className = `message-box ${type}`;
    messageBox.textContent = message;

    // Insert before form
    const form = document.querySelector('form');
    form.parentNode.insertBefore(messageBox, form);

    // Auto remove after 5 seconds
    setTimeout(() => {
        messageBox.remove();
    }, 5000);
}

// Check if user is already logged in
function checkLoginStatus() {
    const userId = localStorage.getItem('userId');
    const userType = localStorage.getItem('userType');

    if (userId) {
        // User is already logged in, redirect
        if (userType === 'admin') {
            window.location.href = 'dashboard.html';
        } else {
            window.location.href = 'user_home.html';
        }
    }
}

// Check on page load
checkLoginStatus();
