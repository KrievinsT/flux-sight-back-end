<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <a href="http://127.0.0.1:8000" rel="no-referrer-when-downgrade">…</a>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Styles -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/sass/app.scss'])

    <!-- Scripts -->
    <script>
        function showMessage(message, isError = false) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerText = message;
            messageDiv.style.color = isError ? 'red' : 'green';
            messageDiv.style.display = 'block';
        }

        // Handle Register and Login Form Submission
        async function handleFormSubmit(event, formType) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            // Store data in local storage
            const formEntries = Object.fromEntries(formData);
            localStorage.setItem(formType, JSON.stringify(formEntries));
            console.log(`Stored data for ${formType}:`, formEntries);

            // Send data to the server
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            if (!csrfToken) {
                showMessage('CSRF token not found!', true);
                return;
            }

            // Determine the correct endpoint based on form type
            const endpoint = formType === 'register' ? '/api/pre-register' : '/api/pre-login';

            try {
                // First call preRegister or preLogin
                const preResponse = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const preData = await preResponse.json();
                showMessage(preData.message, preResponse.status !== 200);

                // If preRegister or preLogin is successful, call 2fa/generate
                if (preResponse.status === 200) {
                    // Set the form type for 2FA form
                    document.getElementById('2faForm').setAttribute('data-form-type', formType);

                    const generate2FAResponse = await fetch(`/api/2fa/generate`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    const generate2FAData = await generate2FAResponse.json();
                    showMessage(generate2FAData.message, generate2FAResponse.status !== 200);
                }
            } catch (error) {
                console.error('Error in pre-register/login flow:', error);
                showMessage('An error occurred. Please try again.', true);
            }
        }

        // Handle 2FA Form Submission
        async function handle2FASubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formType = form.getAttribute('data-form-type');
            const formData = new FormData(form);

            const storedData = JSON.parse(localStorage.getItem(formType));
            if (!storedData) {
                showMessage('No registration or login data found!', true);
                return;
            }
            console.log(`Retrieved data for ${formType}:`, storedData);

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const response = await fetch(`/api/2fa/verify`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();
                showMessage(data.message, response.status !== 200);

                if (data.message === '2FA verified successfully.') {
                    const endpoint = formType === 'register' ? '/api/register' : '/api/login';
                    const registerOrLoginResponse = await fetch(endpoint, {
                        method: 'POST',
                        body: new URLSearchParams(storedData),
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    try {
                        const registerOrLoginData = await registerOrLoginResponse.json();
                        showMessage(registerOrLoginData.message, registerOrLoginResponse.status !== 200);

                        if (registerOrLoginResponse.status === 200 && formType === 'login') {
                            showMessage('You are now online', false);
                            console.log('User ID:', registerOrLoginData.user);
                            console.log('Token:', registerOrLoginData.token);
                            localStorage.removeItem('login');
                        }

                        if (registerOrLoginResponse.status === 200 && formType === 'register') {
                            showMessage('Registration successful', false);
                            localStorage.removeItem('register');
                        }
                    } catch (error) {
                        console.error('Error parsing JSON:', error);
                        showMessage('Unexpected error parsing response. Please try again.', true);
                        console.error('Response Text:', await registerOrLoginResponse.text());
                    }
                }
            } catch (error) {
                console.error('Error in 2FA flow:', error);
                showMessage('An error occurred. Please try again.', true);
            }
        }

        async function handleForgotPassword(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            if (!csrfToken) {
                showMessage('CSRF token not found!', true);
                return;
            }

            try {
                const response = await fetch('/api/password/email', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();
                showMessage(data.message, response.status !== 200);

                if (response.status === 200) {
                    showMessage('Password reset link sent to your email.', false);
                }
            } catch (error) {
                console.error('Error in forgot password flow:', error);
                showMessage('An error occurred. Please try again.', true);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            let token = urlParams.get('token');

            if (token) {
                token = token.replace(/^\/+/, ''); // Remove any leading slashes
                document.getElementById('resetToken').value = token;
            } else {
                showMessage('Reset token not found. Please use the link provided in your email.', true);
            }
        });

        async function handleResetPassword(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            // Log form data to verify token
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            console.log('CSRF Token:', csrfToken); // Log the CSRF token to verify
            if (!csrfToken) {
                showMessage('CSRF token not found!', true);
                return;
            }

            try {
                const response = await fetch('/api/password/reset', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const responseText = await response.text(); // Get the response as text
                console.log('Response Text:', responseText); // Log the full response text

                try {
                    const data = JSON.parse(responseText); // Then try to parse the text as JSON
                    showMessage(data.message, response.status !== 200);

                    if (response.status === 200) {
                        showMessage('Password reset successful. You can now log in with your new password.', false);
                    }
                } catch (jsonError) {
                    console.error('Error parsing JSON:', jsonError);
                    showMessage('Unexpected error parsing response. Please try again.', true);
                }
            } catch (fetchError) {
                console.error('Error in reset password flow:', fetchError);
                showMessage('An error occurred. Please try again.', true);
            }
        }
    </script>

</head>

<body class="font-sans antialiased dark:bg-black dark:text-white/50">
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        <img id="background" class="absolute -left-20 top-0 max-w-[877px]"
            src="https://laravel.com/assets/img/welcome/background.svg" alt="Laravel background" />
        <div
            class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                    <div class="flex lg:justify-center lg:col-start-2">
                        <svg class="h-12 w-auto text-white lg:h-16 lg:text-[#FF2D20]" viewBox="0 0 62 65" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                        </svg>
                    </div>
                </header>

                <main class="main">
                    <h2 class="heading">API routes</h2>
                    <div class="routes">
                        <div>
                            <h3 class="name">Post request</h3>
                            <ul>
                                <li class="main"><a href="/api/register">/register</a></li>
                                <li class="main"><a href="/api/login">/login</a></li>
                                <li class="main"><a href="/api/password/email">/password/email</a></li>
                                <!-- Added route -->
                                <li class="main"><a href="/api/password/reset">/password/reset</a></li>
                                <!-- Added route -->
                                <li class="main"><a href="/api/2fa/verify">/2fa/verify</a></li>
                                <li class="main"><a href="/api/web/store">/web/store</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="name">Get request</h3>
                            <ul>
                                <li class="main"><a href="/api/auth/google">/auth/google</a></li>
                                <li class="main"><a href="/api/auth/google/callback">/auth/google/callback</a></li>
                                <li class="main"><a href="/api/web">/web</a></li>
                                <li class="main"><a href="/api/storage">/storage</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="forms">
                        <h3>Register</h3>
                        <form method="POST" action="/api/pre-register" onsubmit="handleFormSubmit(event, 'register')">
                            <input type="text" name="name" placeholder="Name" required>
                            <input type="email" name="email" placeholder="Email" required>
                            <input type="password" name="password" placeholder="Password" required>
                            <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
                            <input type="text" name="phone_number" placeholder="Phone Number" required>
                            <button type="submit">Register</button>
                        </form>

                        <h3>Login</h3>
                        <form method="POST" action="/api/pre-login" onsubmit="handleFormSubmit(event, 'login')">
                            <input type="text" name="login" placeholder="Email or Username" required>
                            <input type="password" name="password" placeholder="Password" required>
                            <button type="submit">Login</button>
                        </form>

                        <h3>Forgot Password</h3>
                        <form method="POST" action="/api/password/email" onsubmit="handleForgotPassword(event)">
                            <input type="email" name="email" placeholder="Email" required>
                            <button type="submit">Send Reset Link</button>
                        </form>

                        <h3>Reset Password</h3>
                        <form method="POST" action="/api/password/reset" onsubmit="handleResetPassword(event)">
                            <input type="email" name="email" placeholder="Email" required>
                            <input type="password" name="password" placeholder="New Password" required>
                            <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
                            <input type="hidden" name="token"
                                value="456e9c0b3a391679afde5a04451e02ff264ba21e0d245f9be66091fc6b641e78"
                                id="resetToken" required>
                            <button type="submit">Reset Password</button>
                        </form>

                        <h3>Verify 2FA</h3>
                        <form id="2faForm" method="POST" action="/api/2fa/verify" data-form-type=""
                            onsubmit="handle2FASubmit(event)">
                            <input type="text" name="two_factor_code" placeholder="2FA Code" required>
                            <button type="submit">Verify</button>
                        </form>
                        <div id="message" style="display: none; margin-top: 10px;"></div>
                    </div>
                </main>
            </div>
        </div>

    </div>
</body>

</html>
