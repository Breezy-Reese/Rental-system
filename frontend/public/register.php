<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../includes/api.php";
require_once __DIR__ . "/../includes/auth.php";

/*
|--------------------------------------------------------------------------
| Redirect users who are already logged in
|--------------------------------------------------------------------------
*/
if (is_logged_in()) {
    redirect_by_role();
}

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Handle Registration
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Validate form
    |--------------------------------------------------------------------------
    */

    if ($name === "") {
        $error = "Please enter your full name.";
    } elseif ($email === "") {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($phone === "") {
        $error = "Please enter your phone number.";
    } elseif ($password === "") {
        $error = "Please enter a password.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($confirmPassword === "") {
        $error = "Please confirm your password.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    }

    /*
    |--------------------------------------------------------------------------
    | Send registration request to API
    | Customer is the ONLY account type created from this form.
    |--------------------------------------------------------------------------
    */
    if ($error === "") {

        $response = api_post("/auth/register", [
            "name" => $name,
            "email" => $email,
            "phone" => $phone,

            // Registration from this page is always Customer.
            "role" => "Customer",

            "password" => $password,
            "confirmPassword" => $confirmPassword
        ]);

        /*
        |--------------------------------------------------------------------------
        | Successful registration
        |--------------------------------------------------------------------------
        */
        if (
            !empty($response["success"]) &&
            !empty($response["data"])
        ) {

            $_SESSION["propertypro_token"] =
                $response["data"]["token"] ?? "";

            $_SESSION["user"] =
                $response["data"]["user"] ?? [];

            // Newly registered users are Customers.
            header("Location: customer/dashboard.php");
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Registration failed
        |--------------------------------------------------------------------------
        */
        $error = $response["message"] ??
            "Registration failed. Please try again.";
    }
}

$pageTitle = "Register";

require_once __DIR__ . "/../includes/header.php";
?>

<div class="min-h-screen relative flex items-center justify-center px-4 py-10 overflow-hidden bg-black">

    <!-- Ambient gold streak background -->
    <div class="pointer-events-none absolute inset-0 opacity-70">
        <div class="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-amber-500/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-[28rem] w-[28rem] rounded-full bg-amber-400/10 blur-3xl"></div>
        <div class="absolute top-1/3 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-yellow-600/10 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-lg">

        <!-- Logo -->
        <div class="text-center mb-8">

            <a
                href="index.php"
                class="inline-block text-3xl font-bold text-amber-400 tracking-tight"
            >
                PropertyPro
            </a>

            <p class="mt-2 text-sm text-zinc-400">
                Register as a PropertyPro customer
            </p>

        </div>


        <!-- Registration Card (glass, chamfered corners) -->
        <div
            class="relative border border-zinc-700/60 bg-zinc-900/70 backdrop-blur-xl shadow-2xl shadow-black/50 p-6 md:p-8"
            style="clip-path: polygon(24px 0, 100% 0, 100% calc(100% - 24px), calc(100% - 24px) 100%, 0 100%, 0 24px);"
        >

            <!-- Corner accent triangles (floating outside the card) -->
            <div
                class="pointer-events-none absolute -top-3 -left-3 h-6 w-6 bg-amber-400"
                style="clip-path: polygon(0 0, 100% 0, 0 100%);"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-3 -right-3 h-6 w-6 bg-amber-400"
                style="clip-path: polygon(100% 100%, 0 100%, 100% 0);"
            ></div>

            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-white">
                    Create <span class="text-amber-400">Account</span>
                </h1>
                <p class="mt-1 text-sm text-zinc-400">
                    Join us today to access your secure workspace
                </p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>

                <div
                    class="mb-6 flex items-start gap-3 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300"
                >
                    <svg
                        class="w-5 h-5 mt-0.5 flex-shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>

                    <span>
                        <?= e($error) ?>
                    </span>
                </div>

            <?php endif; ?>


            <!-- Success Message -->
            <?php if ($success): ?>

                <div
                    class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
                >
                    <?= e($success) ?>
                </div>

            <?php endif; ?>


            <!-- Registration Form -->
            <form
                method="POST"
                action=""
                class="space-y-5"
            >

                <!-- Full Name -->
                <div>

                    <label
                        for="name"
                        class="block text-sm font-medium text-zinc-300 mb-2"
                    >
                        Full Name
                    </label>

                    <div class="relative">

                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e($_POST["name"] ?? "") ?>"
                            required
                            autocomplete="name"
                            placeholder="Alex Johnson"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-800/60 pl-10 pr-4 py-3 text-white placeholder-zinc-500 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                        >

                    </div>

                </div>


                <!-- Email -->
                <div>

                    <label
                        for="email"
                        class="block text-sm font-medium text-zinc-300 mb-2"
                    >
                        Email Address
                    </label>

                    <div class="relative">

                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($_POST["email"] ?? "") ?>"
                            required
                            autocomplete="email"
                            placeholder="name@domain.com"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-800/60 pl-10 pr-4 py-3 text-white placeholder-zinc-500 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                        >

                    </div>

                </div>


                <!-- Phone -->
                <div>

                    <label
                        for="phone"
                        class="block text-sm font-medium text-zinc-300 mb-2"
                    >
                        Phone Number
                    </label>

                    <div class="relative">

                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </span>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= e($_POST["phone"] ?? "") ?>"
                            required
                            autocomplete="tel"
                            placeholder="Enter your phone number"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-800/60 pl-10 pr-4 py-3 text-white placeholder-zinc-500 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                        >

                    </div>

                </div>


                <!-- Password + Confirm Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <!-- Password -->
                    <div>

                        <label
                            for="password"
                            class="block text-sm font-medium text-zinc-300 mb-2"
                        >
                            Password
                        </label>

                        <div class="relative">

                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.1 0 2-.9 2-2V7a2 2 0 10-4 0v2c0 1.1.9 2 2 2zm6 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2v-6a2 2 0 012-2h8a2 2 0 012 2z" />
                                </svg>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-800/60 pl-10 pr-4 py-3 text-white placeholder-zinc-500 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                            >

                        </div>

                        <p class="mt-1.5 text-xs text-zinc-500">
                            Password must be at least 6 characters.
                        </p>

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

                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.1 0 2-.9 2-2V7a2 2 0 10-4 0v2c0 1.1.9 2 2 2zm6 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2v-6a2 2 0 012-2h8a2 2 0 012 2z" />
                                </svg>
                            </span>

                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-800/60 pl-10 pr-4 py-3 text-white placeholder-zinc-500 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                            >

                        </div>

                    </div>

                </div>


                <!-- Terms -->
                <div class="flex items-start gap-3">

                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        required
                        class="mt-1 h-4 w-4 rounded border-zinc-600 bg-zinc-800 text-amber-500 focus:ring-amber-500/40 focus:ring-offset-0"
                    >

                    <label
                        for="terms"
                        class="text-sm text-zinc-400"
                    >
                        I agree to the PropertyPro terms and conditions.
                    </label>

                </div>


                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full rounded-lg bg-gradient-to-r from-amber-400 to-yellow-500 px-4 py-3 font-semibold text-zinc-900 shadow-lg shadow-amber-500/20 transition hover:from-amber-300 hover:to-yellow-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-zinc-900"
                >
                    Create Customer Account
                </button>

            </form>


            <!-- Login Link -->
            <div class="mt-6 text-center text-sm text-zinc-400">

                Already have an account?

                <a
                    href="login.php"
                    class="font-semibold text-amber-400 hover:text-amber-300"
                >
                    Sign In
                </a>

            </div>

        </div>


        <!-- Footer Text -->
        <p class="mt-6 text-center text-xs text-zinc-600">
            PropertyPro Management
        </p>

    </div>

</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>