<?php

require_once __DIR__ . "/../../includes/admin.php";
require_admin();

require_once __DIR__ . "/../../includes/data.php";
require_once __DIR__ . "/../../includes/api.php";

$pageTitle = "Notifications";

$notifications = $notifications ?? [];

if (!is_array($notifications)) {
    $notifications = [];
}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists("notification_escape")) {
    function notification_escape($value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );
    }
}


if (!function_exists("admin_notification_icon")) {
    function admin_notification_icon(
        string $type
    ): string {
        return match ($type) {
            "payment_created",
            "payment_status_changed"
                => "💰",

            "maintenance_created",
            "maintenance_status_changed"
                => "🔧",

            "expense_created"
                => "💳",

            "lease_created"
                => "📄",

            "admin_feedback"
                => "💬",

            default
                => "🔔",
        };
    }
}


if (!function_exists("admin_notification_style")) {
    function admin_notification_style(
        string $type
    ): string {
        return match ($type) {
            "payment_created",
            "payment_status_changed"
                => "bg-green-100 text-green-700",

            "maintenance_created",
            "maintenance_status_changed"
                => "bg-amber-100 text-amber-700",

            "expense_created"
                => "bg-red-100 text-red-700",

            "lease_created"
                => "bg-blue-100 text-blue-700",

            "admin_feedback"
                => "bg-purple-100 text-purple-700",

            default
                => "bg-indigo-100 text-indigo-700",
        };
    }
}


if (!function_exists("admin_notification_time")) {
    function admin_notification_time(
        $date
    ): string {
        if (empty($date)) {
            return "";
        }

        $timestamp = strtotime(
            (string) $date
        );

        if ($timestamp === false) {
            return (string) $date;
        }

        return date(
            "d M Y, H:i",
            $timestamp
        );
    }
}


/*
|--------------------------------------------------------------------------
| Current selected notification
|--------------------------------------------------------------------------
*/

$viewId =
    isset($_GET["view"])
        ? trim((string) $_GET["view"])
        : "";


/*
|--------------------------------------------------------------------------
| Flash messages
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION["notification_success"] ?? "";

$errorMessage =
    $_SESSION["notification_error"] ?? "";

unset(
    $_SESSION["notification_success"],
    $_SESSION["notification_error"]
);


/*
|--------------------------------------------------------------------------
| Send feedback
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "reply"
) {
    $notificationId =
        trim(
            (string) (
                $_POST["notification_id"] ?? ""
            )
        );

    $feedback =
        trim(
            (string) (
                $_POST["message"] ?? ""
            )
        );

    if ($notificationId === "") {
        $_SESSION["notification_error"] =
            "Notification ID is missing.";

        header(
            "Location: notifications.php"
        );

        exit;
    }

    if ($feedback === "") {
        $_SESSION["notification_error"] =
            "Please enter feedback before sending.";

        header(
            "Location: notifications.php?view=" .
            urlencode($notificationId)
        );

        exit;
    }

    if (strlen($feedback) > 2000) {
        $_SESSION["notification_error"] =
            "Feedback cannot exceed 2000 characters.";

        header(
            "Location: notifications.php?view=" .
            urlencode($notificationId)
        );

        exit;
    }

    $replyResult = api_post(
        "/notifications/" .
        rawurlencode($notificationId) .
        "/reply",
        [
            "message" => $feedback,
        ]
    );

    if (
        !empty($replyResult["success"])
    ) {
        $_SESSION["notification_success"] =
            "Feedback sent successfully to the customer.";

        header(
            "Location: notifications.php?view=" .
            urlencode($notificationId)
        );

        exit;
    }

    $_SESSION["notification_error"] =
        $replyResult["message"] ??
        "Failed to send feedback.";

    header(
        "Location: notifications.php?view=" .
        urlencode($notificationId)
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Load selected notification
|--------------------------------------------------------------------------
*/

$selectedNotification = null;

if ($viewId !== "") {
    $notificationResult =
        api_get(
            "/notifications/" .
            rawurlencode($viewId)
        );

    if (
        !empty($notificationResult["success"]) &&
        !empty($notificationResult["data"])
    ) {
        $selectedNotification =
            $notificationResult["data"];

        /*
        | Automatically mark it as read
        */
        if (
            empty(
                $selectedNotification["read"]
            )
        ) {
            api_patch(
                "/notifications/" .
                rawurlencode($viewId) .
                "/read"
            );

            $selectedNotification["read"] = true;
        }
    } else {
        $errorMessage =
            $notificationResult["message"] ??
            "Unable to load notification.";
    }
}


/*
|--------------------------------------------------------------------------
| Recalculate notification counts
|--------------------------------------------------------------------------
*/

$totalCount =
    count($notifications);

$unreadCount = 0;

foreach ($notifications as $notification) {
    if (
        empty(
            $notification["read"]
        )
    ) {
        $unreadCount++;
    }
}


require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

?>

<main class="min-w-0 flex-1 lg:ml-64">

    <div class="w-full min-w-0 p-4 sm:p-6 lg:p-8">

        <!-- Page Header -->

        <div class="mb-8">

            <h1 class="text-2xl font-bold text-slate-900">
                Notifications
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View customer activity, read notifications,
                and send feedback to customers.
            </p>

        </div>


        <!-- Messages -->

        <?php if (!empty($successMessage)): ?>

            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                <div class="flex items-center gap-3">
                    <span class="text-lg">✓</span>

                    <span>
                        <?= notification_escape(
                            $successMessage
                        ) ?>
                    </span>
                </div>
            </div>

        <?php endif; ?>


        <?php if (!empty($errorMessage)): ?>

            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <div class="flex items-center gap-3">
                    <span class="text-lg">⚠</span>

                    <span>
                        <?= notification_escape(
                            $errorMessage
                        ) ?>
                    </span>
                </div>
            </div>

        <?php endif; ?>


        <!-- Statistics -->

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Total Notifications
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-900">
                            <?= $totalCount ?>
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-2xl">
                        🔔
                    </div>

                </div>

            </div>


            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Unread
                        </p>

                        <p class="mt-2 text-3xl font-bold text-indigo-600">
                            <?= $unreadCount ?>
                        </p>

                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-2xl">
                        📩
                    </div>

                </div>

            </div>

        </div>


        <?php if ($selectedNotification): ?>

            <?php

            $selectedId =
                $selectedNotification["id"]
                ?? $selectedNotification["_id"]
                ?? "";

            $selectedType =
                $selectedNotification["type"]
                ?? "system";

            $selectedTitle =
                $selectedNotification["title"]
                ?? "Notification";

            $selectedMessage =
                $selectedNotification["message"]
                ?? "";

            $selectedDate =
                $selectedNotification["createdAt"]
                ?? $selectedNotification["date"]
                ?? "";

            $customer =
                $selectedNotification["customer"]
                ?? [];

            ?>

            <!-- Notification Details -->

            <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-4 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex items-center gap-4">

                        <div class="flex h-12 w-12 items-center justify-center rounded-xl text-xl <?= admin_notification_style($selectedType) ?>">
                            <?= admin_notification_icon($selectedType) ?>
                        </div>

                        <div>

                            <h2 class="text-lg font-bold text-slate-900">
                                <?= notification_escape($selectedTitle) ?>
                            </h2>

                            <p class="text-sm text-slate-500">
                                <?= notification_escape(
                                    admin_notification_time($selectedDate)
                                ) ?>
                            </p>

                        </div>

                    </div>


                    <a
                        href="notifications.php"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        ← Back to Notifications
                    </a>

                </div>


                <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-3">

                    <!-- Customer -->

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <h3 class="mb-4 font-semibold text-slate-900">
                            Customer
                        </h3>


                        <?php if (!empty($customer)): ?>

                            <div class="space-y-4">

                                <div>

                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                        Name
                                    </p>

                                    <p class="mt-1 font-semibold text-slate-900">
                                        <?= notification_escape(
                                            $customer["name"] ?? "N/A"
                                        ) ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                        Email
                                    </p>

                                    <p class="mt-1 break-all text-sm text-slate-700">
                                        <?= notification_escape(
                                            $customer["email"] ?? "N/A"
                                        ) ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                        Phone
                                    </p>

                                    <p class="mt-1 text-sm text-slate-700">
                                        <?= notification_escape(
                                            $customer["phone"] ?? "N/A"
                                        ) ?>
                                    </p>

                                </div>

                            </div>

                        <?php else: ?>

                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">

                                <p class="text-sm text-amber-700">
                                    Customer information could not be linked to this notification.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- Notification -->

                    <div class="lg:col-span-2">

                        <div>

                            <h3 class="mb-3 font-semibold text-slate-900">
                                Notification Message
                            </h3>

                            <div class="rounded-xl border border-slate-200 bg-white p-5">

                                <p class="whitespace-pre-wrap text-sm leading-7 text-slate-700">
                                    <?= notification_escape(
                                        $selectedMessage
                                    ) ?>
                                </p>

                            </div>

                        </div>


                        <!-- Feedback -->

                        <div class="mt-6">

                            <h3 class="mb-3 font-semibold text-slate-900">
                                Send Feedback
                            </h3>

                            <form
                                method="POST"
                                action="notifications.php?view=<?= urlencode($selectedId) ?>"
                                class="rounded-xl border border-slate-200 bg-slate-50 p-5"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="reply"
                                >

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= notification_escape($selectedId) ?>"
                                >


                                <label
                                    for="feedback"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Your feedback
                                </label>


                                <textarea
                                    id="feedback"
                                    name="message"
                                    rows="5"
                                    maxlength="2000"
                                    required
                                    placeholder="Write your feedback or response to the customer..."
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                ></textarea>


                                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                                    <p class="text-xs text-slate-400">
                                        Maximum 2000 characters.
                                    </p>


                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        📤 Send Feedback
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        <?php endif; ?>


        <!-- Notifications List -->

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h2 class="font-semibold text-slate-900">
                        Recent Notifications
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Select a notification to read and respond.
                    </p>

                </div>


                <?php if ($unreadCount > 0): ?>

                    <span class="inline-flex w-fit items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                        <?= $unreadCount ?> unread
                    </span>

                <?php endif; ?>

            </div>


            <?php if (empty($notifications)): ?>

                <div class="px-6 py-16 text-center">

                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
                        🔔
                    </div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        No notifications
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Customer activities and system alerts will appear here.
                    </p>

                </div>

            <?php else: ?>

                <div class="divide-y divide-slate-100">

                    <?php foreach ($notifications as $notification): ?>

                        <?php

                        $notificationId =
                            $notification["id"]
                            ?? $notification["_id"]
                            ?? "";

                        $type =
                            $notification["type"]
                            ?? "system";

                        $title =
                            $notification["title"]
                            ?? "Notification";

                        $message =
                            $notification["message"]
                            ?? "";

                        $date =
                            $notification["createdAt"]
                            ?? $notification["date"]
                            ?? "";

                        $read =
                            !empty(
                                $notification["read"]
                            );

                        ?>

                        <div
                            class="<?= $read
                                ? "bg-white"
                                : "bg-indigo-50/40"
                            ?> p-5 transition hover:bg-slate-50"
                        >

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">

                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl <?= admin_notification_style($type) ?>"
                                >
                                    <?= admin_notification_icon($type) ?>
                                </div>


                                <div class="min-w-0 flex-1">

                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">

                                        <div class="min-w-0">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <h3 class="font-semibold text-slate-900">
                                                    <?= notification_escape($title) ?>
                                                </h3>


                                                <?php if (!$read): ?>

                                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                        New
                                                    </span>

                                                <?php else: ?>

                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                                        Read
                                                    </span>

                                                <?php endif; ?>

                                            </div>


                                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                                <?= notification_escape($message) ?>
                                            </p>


                                            <p class="mt-2 text-xs text-slate-400">
                                                <?= notification_escape(
                                                    admin_notification_time($date)
                                                ) ?>
                                            </p>

                                        </div>


                                        <div class="shrink-0">

                                            <?php if ($notificationId !== ""): ?>

                                                <a
                                                    href="notifications.php?view=<?= urlencode($notificationId) ?>"
                                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                                >
                                                    👁 View
                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>