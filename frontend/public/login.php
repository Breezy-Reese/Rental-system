<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api.php';

if (is_logged_in()) {
    redirect_by_role();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {

        $response = api_request(
            'POST',
            '/auth/login',
            [
                'email' => $email,
                'password' => $password
            ]
        );

        if (!empty($response['success'])) {

            $token = $response['token']
                ?? $response['data']['token']
                ?? null;

            $user = $response['user']
                ?? $response['data']['user']
                ?? null;

            if ($token && $user) {

                session_regenerate_id(true);

                $_SESSION['token'] = $token;
                $_SESSION['propertypro_token'] = $token;
                $_SESSION['user'] = $user;

                redirect_by_role();

                exit;
            }

            $error = 'Login succeeded, but authentication information was not returned.';
        } else {
            $error = $response['message'] ?? 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';

include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen bg-black text-white relative overflow-hidden">

    <div class="absolute inset-0 pointer-events-none">

        <div class="absolute -top-32 -left-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl"></div>

        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl"></div>

    </div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">

        <div class="w-full max-w-md">

            <div class="text-center mb-8">

                <div
                    class="inline-flex items-center justify-center w-16 h-16 mb-4
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
                            d="M3 10.5 12 3l9 7.5M5.25 9.5V20h13.5V9.5M9 20v-6h6v6"
                        />
                    </svg>

                </div>

                <h1 class="text-3xl font-bold tracking-tight text-white">
                    Welcome Back
                </h1>

                <p class="text-zinc-500 mt-2">
                    Sign in to your PropertyPro account
                </p>

            </div>

            <div
                class="relative bg-zinc-950/90 border border-zinc-800
                       shadow-2xl shadow-black/50 backdrop-blur-xl p-8"
                style="clip-path: polygon(18px 0, 100% 0, 100% calc(100% - 18px), calc(100% - 18px) 100%, 0 100%, 0 18px);"
            >

                <div class="absolute top-0 left-0 w-5 h-5 bg-amber-500"></div>

                <div class="absolute bottom-0 right-0 w-5 h-5 bg-amber-500"></div>

                <?php if ($error): ?>

                    <div
                        class="mb-6 border border-red-500/30
                               bg-red-500/10 text-red-400
                               px-4 py-3 text-sm"
                    >
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">

                    <div>

                        <label
                            for="loginEmail"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="loginEmail"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                            required
                            autocomplete="email"
                            placeholder="Enter your email"
                            class="w-full rounded-lg border border-zinc-700
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

                    <div>

                        <label
                            for="loginPassword"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Password
                        </label>

                        <div class="relative">

                            <input
                                type="password"
                                id="loginPassword"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full rounded-lg border border-zinc-700
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

                            <button
                                type="button"
                                onclick="showHidePassword('loginPassword', this)"
                                aria-label="Show password"
                                class="absolute right-3 top-1/2
                                       -translate-y-1/2
                                       text-zinc-400
                                       hover:text-amber-400
                                       transition
                                       focus:outline-none"
                            >

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
                        Sign In
                    </button>

                </form>

                <div class="mt-8 pt-6 border-t border-zinc-800 text-center">

                    <p class="text-sm text-zinc-500">

                        Don't have an account?

                        <a
                            href="register.php"
                            class="text-amber-400
                                   hover:text-amber-300
                                   font-medium
                                   transition"
                        >
                            Create an account
                        </a>

                    </p>

                </div>

            </div>

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

<?php
include __DIR__ . '/../includes/footer.php';
?>
