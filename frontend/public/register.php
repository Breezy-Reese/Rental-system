<?php

$pageTitle = "Create Account";

require_once "../includes/header.php";

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

            <form method="POST" action="">

                <div class="grid gap-5 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            First name
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            required
                            class="w-full rounded-lg border border-slate-300
                                   px-4 py-3 text-sm outline-none
                                   focus:border-primary-500">

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            Last name
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            required
                            class="w-full rounded-lg border border-slate-300
                                   px-4 py-3 text-sm outline-none
                                   focus:border-primary-500">

                    </div>

                </div>


                <div class="mt-5">

                    <label class="mb-2 block text-sm font-medium">
                        Email address
                    </label>

                    <input
                        type="email"
                        name="email"
                        required
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500">

                </div>


                <div class="mt-5">

                    <label class="mb-2 block text-sm font-medium">
                        Phone number
                    </label>

                    <input
                        type="tel"
                        name="phone"
                        placeholder="+254..."
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500">

                </div>


                <div class="mt-5">

                    <label class="mb-2 block text-sm font-medium">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500">

                </div>


                <div class="mt-5">

                    <label class="mb-2 block text-sm font-medium">
                        Confirm password
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        class="w-full rounded-lg border border-slate-300
                               px-4 py-3 text-sm outline-none
                               focus:border-primary-500">

                </div>


                <button
                    type="submit"
                    class="mt-6 w-full rounded-lg bg-primary-600
                           px-4 py-3 text-sm font-semibold
                           text-white hover:bg-primary-700">

                    Create Account

                </button>

            </form>


            <p class="mt-6 text-center text-sm text-slate-500">

                Already have an account?

                <a href="login.php"
                   class="font-semibold text-primary-600">

                    Sign in

                </a>

            </p>

        </div>

    </div>

</div>

</body>
</html>