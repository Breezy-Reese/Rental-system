<?php

require_once "../includes/auth.php";

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (
        $email === 'admin@example.com' &&
        $password === 'admin123'
    ) {

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'name' => 'Property Manager',
            'email' => $email,
            'role' => 'Administrator'
        ];

        header("Location: dashboard.php");
        exit;

    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = "Login";

require_once "../includes/header.php";
?>

<div class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-8">

    <div class="w-full max-w-md">

        <!-- Logo -->
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-lg">

                <svg
                    class="h-9 w-9"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 12l9-9 9 9M5 10v10h14V10M9 20v-6h6v6"
                    />
                </svg>

            </div>

            <h1 class="text-2xl font-bold text-slate-900">
                PropertyPro
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Rental Management System
            </p>
        </div>


        <!-- Login Card -->
        <div class="rounded-2xl bg-white p-8 shadow-xl">

            <div class="mb-6">
                <h2 class="text-xl font-semibold text-slate-900">
                    Welcome back
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Sign in to your property management account.
                </p>
            </div>


            <!-- Error -->
            <?php if ($error): ?>

                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <!-- Success -->
            <?php if ($success): ?>

                <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    <?= htmlspecialchars($success) ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="">

                <!-- Email -->
                <div class="mb-5">

                    <label
                        for="email"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="admin@example.com"
                        autocomplete="email"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                    >

                </div>


                <!-- Password -->
                <div class="mb-5">

                    <div class="mb-2 flex items-center justify-between">

                        <label
                            for="password"
                            class="block text-sm font-medium text-slate-700"
                        >
                            Password
                        </label>

                        <a
                            href="#"
                            class="text-sm font-medium text-primary-600 hover:text-primary-700"
                        >
                            Forgot password?
                        </a>

                    </div>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                    >

                </div>


                <!-- Remember -->
                <div class="mb-6 flex items-center">

                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                    >

                    <label
                        for="remember"
                        class="ml-2 text-sm text-slate-600"
                    >
                        Remember me
                    </label>

                </div>


                <!-- Login Button -->
                <button
                    type="submit"
                    class="w-full rounded-lg bg-primary-600 px-4 py-3 font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Sign In
                </button>

            </form>


            <!-- Register -->
            <div class="mt-6 border-t border-slate-200 pt-6 text-center">

                <p class="text-sm text-slate-500">
                    Don't have an account?

                    <a
                        href="register.php"
                        class="font-semibold text-primary-600 hover:text-primary-700"
                    >
                        Create account
                    </a>
                </p>

            </div>

        </div>


        <!-- Demo credentials -->
        <div class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4">

            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                Demo Login
            </p>

            <p class="mt-2 text-sm text-blue-800">
                Email:
                <strong>admin@example.com</strong>
            </p>

            <p class="text-sm text-blue-800">
                Password:
                <strong>admin123</strong>
            </p>

        </div>

    </div>

</div>

<?php
require_once "../includes/footer.php";
?>