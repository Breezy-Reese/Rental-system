<?php

require_once "../includes/auth.php";
require_once "../includes/api.php";

/**
 * ============================================================
 * REGISTRATION PAGE
 * ============================================================
 *
 * All accounts created through public registration are
 * automatically Customer accounts.
 *
 * Administrator accounts are created separately.
 * ============================================================
 */

if (is_logged_in()) {
    redirect_by_role();
}

$error = "";

$firstName = "";
$lastName = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    $password = $_POST["password"] ?? "";
    $passwordConfirmation = $_POST["password_confirmation"] ?? "";

    /**
     * ----------------------------------------------------------
     * VALIDATION
     * ----------------------------------------------------------
     */

    if ($firstName === "" || $lastName === "") {

        $error = "Please enter your first and last name.";

    } elseif (
        $email === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = "Please enter a valid email address.";

    } elseif ($password === "") {

        $error = "Please enter a password.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters long.";

    } elseif ($password !== $passwordConfirmation) {

        $error = "Passwords do not match.";
    }

    /**
     * ----------------------------------------------------------
     * SEND REGISTRATION TO NODE.JS
     * ----------------------------------------------------------
     */
    if ($error === "") {

        $name = trim($firstName . " " . $lastName);

        $result = api_request(
            "POST",
            "/auth/register",
            [
                "name" => $name,
                "email" => $email,
                "password" => $password,
                "phone" => $phone,
            ]
        );

        if (!empty($result["success"])) {

            $data = $result["data"] ?? [];

            $token = $data["token"] ?? null;
            $user = $data["user"] ?? null;

            if ($token && $user) {

                /**
                 * Store authentication session.
                 */
                $_SESSION["propertypro_token"] = $token;
                $_SESSION["user"] = $user;

                /**
                 * New public accounts are always customers.
                 */
                $_SESSION["user"]["role"] = "Customer";

                /**
                 * Send customer to customer dashboard.
                 */
                header("Location: customer/dashboard.php");
                exit;
            }

            $error =
                "Account was created, but the login session could not be started.";

        } else {

            $error =
                $result["message"] ??
                "Registration failed. Please try again.";
        }
    }
}

$pageTitle = "Create Account";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?> | PropertyPro
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: "#eef2ff",
                            100: "#e0e7ff",
                            200: "#c7d2fe",
                            300: "#a5b4fc",
                            400: "#818cf8",
                            500: "#6366f1",
                            600: "#4f46e5",
                            700: "#4338ca",
                            800: "#3730a3",
                            900: "#312e81"
                        }
                    }
                }
            }
        };
    </script>

</head>

<body class="min-h-screen bg-slate-50">

<div class="min-h-screen flex">

    <!-- ========================================================
         LEFT SIDE
    ========================================================= -->

    <div
        class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-700 via-primary-600 to-indigo-800 text-white p-12 items-center"
    >

        <div class="max-w-lg mx-auto">

            <div class="mb-8">

                <div
                    class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center mb-6"
                >

                    <svg
                        class="w-8 h-8"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1"
                        />

                    </svg>

                </div>

                <h1 class="text-4xl font-bold mb-4">
                    Welcome to PropertyPro
                </h1>

                <p class="text-indigo-100 text-lg leading-relaxed">
                    Manage properties, tenants, leases, payments and
                    maintenance from one powerful platform.
                </p>

            </div>

            <div class="space-y-5">

                <div class="flex items-center gap-4">

                    <div
                        class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center"
                    >
                        ✓
                    </div>

                    <span>
                        Easy property management
                    </span>

                </div>

                <div class="flex items-center gap-4">

                    <div
                        class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center"
                    >
                        ✓
                    </div>

                    <span>
                        Track rent and payments
                    </span>

                </div>

                <div class="flex items-center gap-4">

                    <div
                        class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center"
                    >
                        ✓
                    </div>

                    <span>
                        Submit maintenance requests
                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================
         RIGHT SIDE
    ========================================================= -->

    <div
        class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10"
    >

        <div class="w-full max-w-md">

            <!-- Logo -->

            <div class="text-center mb-8">

                <div
                    class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary-600 text-white mb-4"
                >

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011 1v4a1 1 0 001 1"
                        />

                    </svg>

                </div>

                <h2 class="text-2xl font-bold text-slate-900">
                    Create your account
                </h2>

                <p class="text-slate-500 mt-2">
                    Create your PropertyPro customer account
                </p>

            </div>


            <!-- Error -->

            <?php if ($error): ?>

                <div
                    class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action=""
                class="space-y-5"
            >

                <!-- Name -->

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>

                        <label
                            for="first_name"
                            class="block text-sm font-medium text-slate-700 mb-2"
                        >
                            First name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?= htmlspecialchars($firstName) ?>"
                            required
                            autocomplete="given-name"
                            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                            placeholder="First name"
                        >

                    </div>


                    <div>

                        <label
                            for="last_name"
                            class="block text-sm font-medium text-slate-700 mb-2"
                        >
                            Last name
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?= htmlspecialchars($lastName) ?>"
                            required
                            autocomplete="family-name"
                            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                            placeholder="Last name"
                        >

                    </div>

                </div>


                <!-- Email -->

                <div>

                    <label
                        for="email"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Email address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        autocomplete="email"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                        placeholder="you@example.com"
                    >

                </div>


                <!-- Phone -->

                <div>

                    <label
                        for="phone"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Phone number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars($phone) ?>"
                        autocomplete="tel"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                        placeholder="07XXXXXXXX"
                    >

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
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                        placeholder="At least 6 characters"
                    >

                </div>


                <!-- Confirm Password -->

                <div>

                    <label
                        for="password_confirmation"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Confirm password
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                        placeholder="Repeat your password"
                    >

                </div>


                <!-- Submit -->

                <button
                    type="submit"
                    class="w-full rounded-lg bg-primary-600 px-4 py-3 font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Create Customer Account
                </button>

            </form>


            <!-- Login -->

            <p class="text-center text-sm text-slate-600 mt-8">

                Already have an account?

                <a
                    href="login.php"
                    class="font-semibold text-primary-600 hover:text-primary-700"
                >
                    Sign in
                </a>

            </p>


            <p class="text-center text-xs text-slate-400 mt-6">
                © <?= date("Y") ?> PropertyPro Management
            </p>

        </div>

    </div>

</div>

</body>

</html>