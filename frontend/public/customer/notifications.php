<?php

$pageTitle = "Notifications";

require_once __DIR__ . "/../../includes/auth.php";

require_login();

if (current_role() !== "Customer") {
    header("Location: ../admin/dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Demo notifications
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["notifications"])) {

    $_SESSION["notifications"] = [

        [
            "id" => "NOT-001",
            "type" => "payment",
            "title" => "Payment received",
            "message" => "Your rent payment of KSh 25,000 has been received successfully.",
            "date" => date("Y-m-d H:i:s"),
            "read" => false,
        ],

        [
            "id" => "NOT-002",
            "type" => "maintenance",
            "title" => "Maintenance request updated",
            "message" => "Your maintenance request has been received and is being reviewed.",
            "date" => date(
                "Y-m-d H:i:s",
                strtotime("-1 day")
            ),
            "read" => false,
        ],

        [
            "id" => "NOT-003",
            "type" => "lease",
            "title" => "Lease information",
            "message" => "Your lease information is available in the My Lease section.",
            "date" => date(
                "Y-m-d H:i:s",
                strtotime("-2 days")
            ),
            "read" => true,
        ],
    ];
}

/*
|--------------------------------------------------------------------------
| Notification actions
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action =
        $_POST["action"] ?? "";

    $notificationId =
        $_POST["notification_id"] ?? "";

    if (
        $action === "mark_read" &&
        $notificationId !== ""
    ) {

        foreach ($_SESSION["notifications"] as &$notification) {

            if ($notification["id"] === $notificationId) {

                $notification["read"] = true;

                break;
            }
        }

        unset($notification);

        header("Location: notifications.php");
        exit;
    }

    if ($action === "mark_all_read") {

        foreach ($_SESSION["notifications"] as &$notification) {
            $notification["read"] = true;
        }

        unset($notification);

        header("Location: notifications.php");
        exit;
    }

    if (
        $action === "delete" &&
        $notificationId !== ""
    ) {

        $_SESSION["notifications"] =
            array_values(
                array_filter(
                    $_SESSION["notifications"],
                    function ($notification) use ($notificationId) {

                        return $notification["id"] !== $notificationId;
                    }
                )
            );

        header("Location: notifications.php");
        exit;
    }

    if ($action === "clear_all") {

        $_SESSION["notifications"] = [];

        header("Location: notifications.php");
        exit;
    }
}

$notifications =
    $_SESSION["notifications"];

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalNotifications =
    count($notifications);

$unreadNotifications = 0;

foreach ($notifications as $notification) {

    if (empty($notification["read"])) {
        $unreadNotifications++;
    }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function notification_e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function notification_icon(string $type): string
{
    return match ($type) {

        "payment" =>
            "💳",

        "maintenance" =>
            "🔧",

        "lease" =>
            "📄",

        "property" =>
            "🏠",

        default =>
            "🔔",
    };
}

function notification_icon_background(string $type): string
{
    return match ($type) {

        "payment" =>
            "bg-emerald-100",

        "maintenance" =>
            "bg-amber-100",

        "lease" =>
            "bg-indigo-100",

        "property" =>
            "bg-sky-100",

        default =>
            "bg-slate-100",
    };
}

function notification_time(string $date): string
{
    $timestamp =
        strtotime($date);

    if (!$timestamp) {
        return "";
    }

    $difference =
        time() - $timestamp;

    if ($difference < 60) {
        return "Just now";
    }

    if ($difference < 3600) {

        $minutes =
            floor($difference / 60);

        return $minutes . " min ago";
    }

    if ($difference < 86400) {

        $hours =
            floor($difference / 3600);

        return $hours . " hr ago";
    }

    if ($difference < 604800) {

        $days =
            floor($difference / 86400);

        return $days .
            " day" .
            ($days > 1 ? "s" : "") .
            " ago";
    }

    return date(
        "d M Y",
        $timestamp
    );
}

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

?>

<div class="lg:pl-64">

    <main class="min-h-screen p-4 sm:p-6 lg:p-8">

        <!-- Header -->

        <div class="mb-8">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h1 class="text-2xl font-bold text-slate-900">
                        Notifications
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Stay updated with your rental account.
                    </p>

                </div>

                <?php if ($unreadNotifications > 0): ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="action"
                            value="mark_all_read"
                        >

                        <button
                            type="submit"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            Mark all as read
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

        <!-- Statistics -->

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-slate-500">
                            Total Notifications
                        </p>

                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            <?= $totalNotifications ?>
                        </p>

                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-xl">
                        🔔
                    </div>

                </div>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-slate-500">
                            Unread
                        </p>

                        <p class="mt-2 text-2xl font-bold text-indigo-600">
                            <?= $unreadNotifications ?>
                        </p>

                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-xl">
                        ✉️
                    </div>

                </div>

            </div>

        </div>

        <!-- Notifications -->

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <h2 class="text-lg font-semibold text-slate-900">
                            Recent Notifications
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Updates related to your rental account.
                        </p>

                    </div>

                    <?php if ($totalNotifications > 0): ?>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="clear_all"
                            >

                            <button
                                type="submit"
                                onclick="return confirm('Clear all notifications?')"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Clear all
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

            <?php if (empty($notifications)): ?>

                <div class="px-6 py-16 text-center">

                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
                        🔔
                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        No notifications
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        You're all caught up. New rental account updates will appear here.
                    </p>

                </div>

            <?php else: ?>

                <div class="divide-y divide-slate-100">

                    <?php foreach ($notifications as $notification): ?>

                        <?php

                        $isRead =
                            !empty($notification["read"]);

                        $type =
                            $notification["type"]
                            ?? "general";

                        ?>

                        <div
                            class="<?= $isRead ? "bg-white" : "bg-indigo-50/40" ?> px-6 py-5 transition hover:bg-slate-50"
                        >

                            <div class="flex min-w-0 items-start gap-4">

                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xl <?= notification_icon_background($type) ?>"
                                >
                                    <?= notification_icon($type) ?>
                                </div>

                                <div class="min-w-0 flex-1">

                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">

                                        <div class="min-w-0">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <h3 class="font-semibold text-slate-900">
                                                    <?= notification_e(
                                                        $notification["title"]
                                                        ?? "Notification"
                                                    ) ?>
                                                </h3>

                                                <?php if (!$isRead): ?>

                                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                                        New
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                            <p class="mt-1 break-words text-sm leading-6 text-slate-600">
                                                <?= notification_e(
                                                    $notification["message"]
                                                    ?? ""
                                                ) ?>
                                            </p>

                                        </div>

                                        <span class="shrink-0 text-xs text-slate-400">
                                            <?= notification_e(
                                                notification_time(
                                                    $notification["date"]
                                                    ?? ""
                                                )
                                            ) ?>
                                        </span>

                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-3">

                                        <?php if (!$isRead): ?>

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="mark_read"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="notification_id"
                                                    value="<?= notification_e(
                                                        $notification["id"]
                                                    ) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-700"
                                                >
                                                    Mark as read
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >

                                            <input
                                                type="hidden"
                                                name="notification_id"
                                                value="<?= notification_e(
                                                    $notification["id"]
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="text-xs font-semibold text-red-600 hover:text-red-700"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>