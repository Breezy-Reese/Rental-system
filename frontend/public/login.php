<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/api.php";

/*
|--------------------------------------------------------------------------
| Already Logged In
|--------------------------------------------------------------------------
*/

if (is_logged_in()) {
    redirect_by_role();
}

$error = '';

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    } else {

        $result = api_request(
            'POST',
            '/auth/login',
            [
                'email' => $email,
                'password' => $password,
            ]
        );

        if (
            !empty($result['success']) &&
            !empty($result['data']['token']) &&
            !empty($result['data']['user'])
        ) {

            $user = $result['data']['user'];

            /*
             * Normalize role returned by backend.
             */
            $rawRole = $user['role'] ?? '';

            $normalizedRole = strtolower(trim((string) $rawRole));

            if ($normalizedRole === 'customer') {
                $user['role'] = 'Customer';
            } elseif (
                $normalizedRole === 'administrator' ||
                $normalizedRole === 'admin'
            ) {
                $user['role'] = 'Administrator';
            } else {
                $error = 'Your account has an invalid role. Please contact the administrator.';
            }

            if ($error === '') {

                session_regenerate_id(true);

                /*
                 * Store JWT.
                 */
                $_SESSION['propertypro_token'] =
                    $result['data']['token'];

                /*
                 * Store authenticated API user.
                 */
                $_SESSION['user'] = $user;

                redirect_by_role();
            }

        } else {

            $error =
                $result['message']
                ?? 'Invalid email or password.';
        }
    }
}

$pageTitle = "Login";

require_once __DIR__ . "/../includes/header.php";
?>

<div class="flex min-h-screen items-center justify-center px-4 py-12">

    <div class="w-full max-w-md">

        <!-- Logo -->
        <div class="mb-8 text-center">

            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-indigo-600 text-2xl font-bold text-white">
                P
            </div>

            <h1 class="text-2xl font-bold text-slate-900">
                Welcome to PropertyPro
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Property Rental Management System
            </p>

        </div>

        <!-- Login Card -->
        <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200 sm:p-8">

            <?php if ($error): ?>

                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <form
                method="POST"
                action="login.php"
                class="space-y-5"
            >

                <div>

                    <label
                        for="email"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Email Address
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        placeholder="Enter your email"
                        required
                        autocomplete="email"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                    >

                </div>

                <div>

                    <label
                        for="password"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                    >

                </div>

                <div class="flex items-center justify-between">

                    <label class="flex items-center gap-2 text-sm text-slate-600">

                        <input
                            type="checkbox"
                            name="remember"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >

                        Remember me

                    </label>

                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Sign In
                </button>

            </form>

            <div class="mt-6 text-center text-sm text-slate-600">

                Don't have an account?

                <a
                    href="register.php"
                    class="font-semibold text-indigo-600 hover:text-indigo-700"
                >
                    Create an account
                </a>

            </div>

        </div>

    </div>

</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>