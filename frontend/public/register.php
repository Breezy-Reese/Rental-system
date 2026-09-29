<?php

session_start();

require_once __DIR__ . "/../includes/api.php";

/*
|--------------------------------------------------------------------------
| Redirect already logged-in users
|--------------------------------------------------------------------------
*/
if (!empty($_SESSION["user"])) {
    $role = $_SESSION["user"]["role"] ?? "";

    if ($role === "Administrator") {
        header("Location: admin/dashboard.php");
        exit;
    }

    if ($role === "Customer") {
        header("Location: customer/dashboard.php");
        exit;
    }
}

$error = "";

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
     * Basic validation
     */
    if ($name === "") {
        $error = "Please enter your full name.";
    } elseif ($email === "") {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password === "") {
        $error = "Please create a password.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($confirmPassword === "") {
        $error = "Please confirm your password.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {

        /*
         * IMPORTANT:
         *
         * There is intentionally NO role value here.
         *
         * The backend automatically creates this account
         * as a Customer.
         */
        $response = api_post("/auth/register", [
            "name" => $name,
            "email" => $email,
            "phone" => $phone,
            "password" => $password,
            "confirmPassword" => $confirmPassword
        ]);

        /*
         * Registration successful
         */
        if (
            !empty($response["success"]) &&
            !empty($response["data"])
        ) {
            $_SESSION["propertypro_token"] =
                $response["data"]["token"] ?? "";

            $_SESSION["user"] =
                $response["data"]["user"] ?? [];

            /*
             * Public registration is Customer-only.
             */
            header("Location: customer/dashboard.php");
            exit;
        }

        /*
         * Registration failed
         */
        $error = $response["message"] ??
            "Registration failed. Please try again.";
    }
}

$pageTitle = "Create Customer Account";

require_once __DIR__ . "/../includes/header.php";
?>

<div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-md">

        <!-- Brand -->
        <div class="text-center mb-8">

            <a
                href="index.php"
                class="inline-block text-3xl font-bold text-indigo-600"
            >
                PropertyPro
            </a>

            <h1 class="mt-4 text-2xl font-bold text-slate-900">
                Create Your Account
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Create your PropertyPro customer account
            </p>

        </div>


        <!-- Registration Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">

            <?php if ($error): ?>

                <div
                    class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                class="space-y-5"
                autocomplete="off"
            >

                <!-- Full Name -->
                <div>

                    <label
                        for="name"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($_POST["name"] ?? "") ?>"
                        required
                        autocomplete="name"
                        placeholder="Enter your full name"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <!-- Email -->
                <div>

                    <label
                        for="email"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($_POST["email"] ?? "") ?>"
                        required
                        autocomplete="email"
                        placeholder="Enter your email address"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <!-- Phone -->
                <div>

                    <label
                        for="phone"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= e($_POST["phone"] ?? "") ?>"
                        autocomplete="tel"
                        placeholder="Enter your phone number"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <!-- Account Type -->
                <div>

                    <label
                        for="accountType"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Account Type
                    </label>

                    <input
                        type="text"
                        id="accountType"
                        value="Customer"
                        readonly
                        aria-readonly="true"
                        class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-slate-600 cursor-not-allowed"
                    >

                    <p class="mt-1.5 text-xs text-slate-500">
                        Public registrations are for Customer accounts only.
                    </p>

                </div>


                <!-- Password -->
                <div>

                    <label
                        for="password"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="Create a password"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                    <p class="mt-1.5 text-xs text-slate-500">
                        Password must contain at least 6 characters.
                    </p>

                </div>


                <!-- Confirm Password -->
                <div>

                    <label
                        for="confirmPassword"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirmPassword"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <!-- Submit -->
                <button
                    type="submit"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Create Customer Account
                </button>

            </form>


            <!-- Login Link -->
            <div class="mt-6 text-center text-sm text-slate-600">

                Already have an account?

                <a
                    href="login.php"
                    class="font-semibold text-indigo-600 hover:text-indigo-700"
                >
                    Sign In
                </a>

            </div>

        </div>


        <!-- Footer -->
        <p class="mt-6 text-center text-xs text-slate-400">
            PropertyPro Management
        </p>

    </div>

</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>

