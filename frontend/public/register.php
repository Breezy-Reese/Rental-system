<?php

$pageTitle = "Create Account";

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (is_logged_in()) {
    redirect_by_role();
}

$error = '';
$success = '';

$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($name === '') {
        $error = 'Please enter your full name.';
    } elseif ($email === '') {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($phone === '') {
        $error = 'Please enter your phone number.';
    } elseif ($password === '') {
        $error = 'Please enter a password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($confirmPassword === '') {
        $error = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {

        /*
         * Public registration is CUSTOMER ONLY.
         * There is deliberately no role selector.
         */
        $response = api_post(
            '/auth/register',
            [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => 'Customer',
                'password' => $password,
                'confirmPassword' => $confirmPassword
            ]
        );

        if (!empty($response['success'])) {

            $token = $response['token']
                ?? $response['data']['token']
                ?? null;

            $user = $response['user']
                ?? $response['data']['user']
                ?? null;

            if ($token) {
                $_SESSION['propertypro_token'] = $token;
                $_SESSION['token'] = $token;
            }

            if ($user) {
                $_SESSION['user'] = $user;
            }

            header('Location: customer/dashboard.php');
            exit;

        } else {

            $error = $response['message']
                ?? 'Registration failed. Please try again.';
        }
    }
}

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="min-h-screen bg-black text-white relative overflow-hidden">

    <!-- Background effects -->
    <div class="absolute inset-0 pointer-events-none">

        <div
            class="absolute -top-32 -left-32
                   w-96 h-96
                   bg-amber-500/10
                   rounded-full
                   blur-3xl"
        ></div>

        <div
            class="absolute -bottom-32 -right-32
                   w-96 h-96
                   bg-amber-600/10
                   rounded-full
                   blur-3xl"
        ></div>

    </div>

    <!-- Registration container -->
    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">

        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="text-center mb-8">

                <div
                    class="inline-flex items-center justify-center
                           w-16 h-16
                           mb-4
                           border border-amber-500/40
                           bg-amber-500/10
                           text-amber-400
                           shadow-lg shadow-amber-500/10"
                    style="clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="w-8 h-8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.7"
                            d="M18 20a6 6 0 0 0-12 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"
                        />
                    </svg>

                </div>

                <h1 class="text-3xl font-bold tracking-tight text-white">
                    Create Account
                </h1>

                <p class="text-zinc-500 mt-2">
                    Join PropertyPro as a customer
                </p>

            </div>

            <!-- Registration card -->
            <div
                class="relative
                       bg-zinc-950/90
                       border border-zinc-800
                       shadow-2xl shadow-black/50
                       backdrop-blur-xl
                       p-8"
                style="clip-path: polygon(18px 0, 100% 0, 100% calc(100% - 18px), calc(100% - 18px) 100%, 0 100%, 0 18px);"
            >

                <!-- Corner accents -->
                <div
                    class="absolute top-0 left-0
                           w-5 h-5
                           bg-amber-500"
                ></div>

                <div
                    class="absolute bottom-0 right-0
                           w-5 h-5
                           bg-amber-500"
                ></div>

                <!-- Error -->
                <?php if ($error): ?>

                    <div
                        class="mb-6
                               border border-red-500/30
                               bg-red-500/10
                               text-red-400
                               px-4 py-3
                               text-sm"
                    >
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                <?php endif; ?>

                <!-- Success -->
                <?php if ($success): ?>

                    <div
                        class="mb-6
                               border border-emerald-500/30
                               bg-emerald-500/10
                               text-emerald-400
                               px-4 py-3
                               text-sm"
                    >
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">

                    <!-- Full name -->
                    <div>

                        <label
                            for="name"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            required
                            autocomplete="name"
                            placeholder="Enter your full name"
                            class="w-full rounded-lg
                                   border border-zinc-700
                                   bg-zinc-900/80
                                   px-4 py-3
                                   text-white
                                   placeholder-zinc-500
                                   outline-none
                                   transition
                                   focus:border-amber-500
                                   focus:ring-1
                                   focus:ring-amber-500"
                        >

                    </div>

                    <!-- Email -->
                    <div>

                        <label
                            for="email"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            required
                            autocomplete="email"
                            placeholder="Enter your email"
                            class="w-full rounded-lg
                                   border border-zinc-700
                                   bg-zinc-900/80
                                   px-4 py-3
                                   text-white
                                   placeholder-zinc-500
                                   outline-none
                                   transition
                                   focus:border-amber-500
                                   focus:ring-1
                                   focus:ring-amber-500"
                        >

                    </div>

                    <!-- Phone -->
                    <div>

                        <label
                            for="phone"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                            required
                            autocomplete="tel"
                            placeholder="Enter your phone number"
                            class="w-full rounded-lg
                                   border border-zinc-700
                                   bg-zinc-900/80
                                   px-4 py-3
                                   text-white
                                   placeholder-zinc-500
                                   outline-none
                                   transition
                                   focus:border-amber-500
                                   focus:ring-1
                                   focus:ring-amber-500"
                        >

                    </div>

                    <!-- Password -->
                    <div>

                        <label
                            for="registerPassword"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Password
                        </label>

                        <div class="relative">

                            <input
                                type="password"
                                id="registerPassword"
                                name="password"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="Create a password"
                                class="w-full rounded-lg
                                       border border-zinc-700
                                       bg-zinc-900/80
                                       px-4 py-3 pr-12
                                       text-white
                                       placeholder-zinc-500
                                       outline-none
                                       transition
                                       focus:border-amber-500
                                       focus:ring-1
                                       focus:ring-amber-500"
                            >

                            <!-- Password visibility button -->
                            <button
                                type="button"
                                onclick="showHidePassword('registerPassword', this)"
                                aria-label="Show password"
                                class="absolute right-3 top-1/2
                                       -translate-y-1/2
                                       text-zinc-400
                                       hover:text-amber-400
                                       transition
                                       focus:outline-none"
                            >

                                <!-- Normal eye -->
                                <svg
                                    class="eye-open w-5 h-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6-9.75-6-9.75-6Z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                    />
                                </svg>

                                <!-- Eye with strike -->
                                <svg
                                    class="eye-closed hidden w-5 h-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 3l18 18"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M10.58 10.58a2 2 0 0 0 2.84 2.84"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9.88 5.09A10.8 10.8 0 0 1 12 4.5c6 0 9.75 7.5 9.75 7.5a18.8 18.8 0 0 1-3.07 3.82"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.61 6.61C3.99 8.33 2.25 12 2.25 12S6 19.5 12 19.5a10.8 10.8 0 0 0 4.12-.81"
                                    />
                                </svg>

                            </button>

                        </div>

                    </div>

                    <!-- Confirm Password -->
                    <div>

                        <label
                            for="confirmPassword"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Confirm Password
                        </label>

                        <div class="relative">

                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="Confirm your password"
                                class="w-full rounded-lg
                                       border border-zinc-700
                                       bg-zinc-900/80
                                       px-4 py-3 pr-12
                                       text-white
                                       placeholder-zinc-500
                                       outline-none
                                       transition
                                       focus:border-amber-500
                                       focus:ring-1
                                       focus:ring-amber-500"
                            >

                            <!-- Password visibility button -->
                            <button
                                type="button"
                                onclick="showHidePassword('confirmPassword', this)"
                                aria-label="Show password"
                                class="absolute right-3 top-1/2
                                       -translate-y-1/2
                                       text-zinc-400
                                       hover:text-amber-400
                                       transition
                                       focus:outline-none"
                            >

                                <!-- Normal eye -->
                                <svg
                                    class="eye-open w-5 h-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6-9.75-6-9.75-6Z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                    />
                                </svg>

                                <!-- Eye with strike -->
                                <svg
                                    class="eye-closed hidden w-5 h-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 3l18 18"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M10.58 10.58a2 2 0 0 0 2.84 2.84"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9.88 5.09A10.8 10.8 0 0 1 12 4.5c6 0 9.75 7.5 9.75 7.5a18.8 18.8 0 0 1-3.07 3.82"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.61 6.61C3.99 8.33 2.25 12 2.25 12S6 19.5 12 19.5a10.8 10.8 0 0 0 4.12-.81"
                                    />
                                </svg>

                            </button>

                        </div>

                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        class="w-full
                               bg-amber-500
                               hover:bg-amber-400
                               active:bg-amber-600
                               text-black
                               font-semibold
                               py-3
                               rounded-lg
                               transition
                               duration-200
                               shadow-lg
                               shadow-amber-500/10"
                    >
                        Create Account
                    </button>

                </form>

                <!-- Login link -->
                <div class="mt-8 pt-6 border-t border-zinc-800 text-center">

                    <p class="text-sm text-zinc-500">

                        Already have an account?

                        <a
                            href="login.php"
                            class="text-amber-400
                                   hover:text-amber-300
                                   font-medium
                                   transition"
                        >
                            Sign in
                        </a>

                    </p>

                </div>

            </div>

            <!-- Footer text -->
            <p class="text-center text-xs text-zinc-600 mt-6">
                PropertyPro Management
            </p>

        </div>

    </div>

</div>

<script>
function showHidePassword(inputId, button) {

    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    const eyeOpen = button.querySelector('.eye-open');
    const eyeClosed = button.querySelector('.eye-closed');

    if (input.type === 'password') {

        input.type = 'text';

        eyeOpen.classList.add('hidden');
        eyeClosed.classList.remove('hidden');

        button.setAttribute('aria-label', 'Hide password');

    } else {

        input.type = 'password';

        eyeOpen.classList.remove('hidden');
        eyeClosed.classList.add('hidden');

        button.setAttribute('aria-label', 'Show password');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
