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
$success = '';

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Get submitted values.
     */
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    /*
     * Combine first and last name because the backend
     * expects one "name" field.
     */
    $name = trim($firstName . ' ' . $lastName);

    /*
     * Basic validation.
     */
    if ($firstName === '' || $lastName === '') {

        $error = 'Please enter your first and last name.';

    } elseif ($email === '') {

        $error = 'Please enter your email address.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif ($password === '') {

        $error = 'Please enter a password.';

    } elseif (strlen($password) < 6) {

        $error = 'Password must contain at least 6 characters.';

    } elseif ($password !== $passwordConfirmation) {

        $error = 'Passwords do not match.';

    } else {

        /*
         * Send registration request to Node.js API.
         *
         * Backend expects:
         *   name
         *   email
         *   password
         *   phone
         */
        $result = api_request(
            'POST',
            '/auth/register',
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'phone' => $phone,
            ]
        );

        /*
         * Registration successful.
         *
         * The backend returns a token and user.
         */
        if (
            !empty($result['success']) &&
            !empty($result['data']['token']) &&
            !empty($result['data']['user'])
        ) {

            $user = $result['data']['user'];

            /*
             * Normalize role.
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

                $error = 'Your account was created, but the account role is invalid. Please contact the administrator.';
            }

            /*
             * Login the newly registered user automatically.
             */
            if ($error === '') {

                session_regenerate_id(true);

                $_SESSION['propertypro_token'] =
                    $result['data']['token'];

                $_SESSION['user'] = $user;

                /*
                 * Customer accounts created through registration
                 * should go to the customer dashboard.
                 */
                redirect_by_role();
            }

        } else {

            /*
             * Display the actual API message.
             */
            $error =
                $result['message'] ??
                'Registration failed. Please try again.';
        }
    }
}

$pageTitle = "Create Account";

require_once __DIR__ . "/../includes/header.php";

?>

<div class="flex min-h-screen items-center justify-center
            bg-slate-100 px-4 py-10">

    <div class="w-full max-w-lg">

        <div class="mb-8 text-center">

            <div class="mx-auto mb-4 flex h-14 w-14
                        items-center justify-center rounded-2xl
                        bg-primary-600 text-white">

                🏠

            </div>

            <h1 class="text-2xl font-bold text-slate-900">
                Create your account
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Start managing your properties today.
            </p>

        </div>

        <div class="rounded-2xl border border-slate-200
                    bg-white p-6 shadow-sm sm:p-8">

            <?php if ($error): ?>

                <div class="mb-6 rounded-lg border border-red-200
                            bg-red-50 px-4 py-3 text-sm text-red-700">

                    <?= e($error) ?>

                </div>

            <?php endif; ?>

            <?php if ($success): ?>

                <div class="mb-6 rounded-lg border border-green-200
                            bg-green-50 px-4 py-3 text-sm text-green-700">

                    <?= e($success) ?>

                </div>

            <?php endif; ?>

            <form method="POST" action="register.php">

                <div class="grid gap-5 sm:grid-cols-2">

                    <div>

                        <label
                            for="first_name"
                            class="mb-2 block text-sm font-medium"
                        >
                            First name
                        </label>

                        <input
                            id="first_name"
                            type="text"
                            name="first_name"
                            value="<?= e($_POST['first_name'] ?? '') ?>"
                            required
                            autocomplete="given-name"
                            class="w-full rounded-lg border border-slate-300
                                   px-4 py-3 text-sm outline-none
                                   focus:border-primary-500"
                        >

                    </div>

                    <div>

                        <label
                            for="last_name"
                            class="mb-2 block text-sm font-medium"
                        >
                            Last name
                        </label>

                        <input
                            id="last_name"
                            type="text"
                            name="last_name"
                            value="<?= e($_POST['last_name'] ?? '') ?>"
                            required
                            autocomplete="family-name"
                            class="w-full rounded-lg border border-slate-300
                                   px-4 py-3 text-sm outline-none
                                   focus:border-primary-500"
                        >

                    </div>

                </div>

                <div class="mt-5">

                    <label
                        for="email"
                        class="mb-2 block text-sm font-medium"
                    >
                        Email address
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500"
                    >

                </div>

                <div class="mt-5">

                    <label
                        for="phone"
                        class="mb-2 block text-sm font-medium"
                    >
                        Phone number
                    </label>

                    <input
                        id="phone"
                        type="tel"
                        name="phone"
                        value="<?= e($_POST['phone'] ?? '') ?>"
                        placeholder="+254..."
                        autocomplete="tel"
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500"
                    >

                </div>

                <div class="mt-5">

                    <label
                        for="password"
                        class="mb-2 block text-sm font-medium"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Password must contain at least 6 characters.
                    </p>

                </div>

                <div class="mt-5">

                    <label
                        for="password_confirmation"
                        class="mb-2 block text-sm font-medium"
                    >
                        Confirm password
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500"
                    >

                </div>

                <button
                    type="submit"
                    class="mt-6 w-full rounded-lg bg-primary-600
                           px-4 py-3 text-sm font-semibold
                           text-white hover:bg-primary-700"
                >
                    Create Account
                </button>

            </form>

            <p class="mt-6 text-center text-sm text-slate-500">

                Already have an account?

                <a
                    href="login.php"
                    class="font-semibold text-primary-600"
                >
                    Sign in
                </a>

            </p>

        </div>

    </div>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

