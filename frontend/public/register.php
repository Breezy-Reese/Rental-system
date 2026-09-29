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
    $role = trim($_POST["role"] ?? "Customer");
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
    | Customer is the only role available on this form
    |--------------------------------------------------------------------------
    */
    if ($error === "" && $role !== "Customer") {
        $error = "Only Customer accounts can be created through registration.";
    }

    /*
    |--------------------------------------------------------------------------
    | Send registration request to API
    |--------------------------------------------------------------------------
    */
    if ($error === "") {

        $response = api_post("/auth/register", [
            "name" => $name,
            "email" => $email,
            "phone" => $phone,
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

            /*
             * Public registration is for Customers.
             */
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

<div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-lg">

        <!-- Logo -->
        <div class="text-center mb-8">

            <a
                href="index.php"
                class="inline-block text-3xl font-bold text-indigo-600"
            >
                PropertyPro
            </a>

            <h1 class="mt-4 text-2xl font-bold text-slate-900">
                Create an Account
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Register as a PropertyPro customer
            </p>

        </div>


        <!-- Registration Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">

            <!-- Error Message -->
            <?php if ($error): ?>

                <div
                    class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
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
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
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
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 placeholder-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
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
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 placeholder-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
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
                        required
                        autocomplete="tel"
                        placeholder="Enter your phone number"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 placeholder-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <!-- Account Type -->
                <div>

                    <label
                        for="role"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Account Type
                    </label>

                    <select
                        id="role"
                        name="role"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="Customer" selected>
                            Customer
                        </option>
                    </select>

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
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 placeholder-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                    <p class="mt-1.5 text-xs text-slate-500">
                        Password must be at least 6 characters.
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
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-900 placeholder-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                <!-- Terms -->
                <div class="flex items-start gap-3">

                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        required
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >

                    <label
                        for="terms"
                        class="text-sm text-slate-600"
                    >
                        I agree to the PropertyPro terms and conditions.
                    </label>

                </div>


                <!-- Submit Button -->
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


        <!-- Footer Text -->
        <p class="mt-6 text-center text-xs text-slate-400">
            PropertyPro Management
        </p>

    </div>

</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>
