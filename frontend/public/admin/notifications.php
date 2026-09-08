<?php

$pageTitle = "Notifications";

require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/admin.php";
require_once __DIR__ . "/../../includes/notifications.php";

require_admin();

/*
|--------------------------------------------------------------------------
| Handle notification actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {

        $id = $_POST['id'] ?? '';

        if ($id !== '') {
            mark_admin_notification_read($id);
        }

    } elseif ($action === 'mark_all_read') {

        mark_all_admin_notifications_read();

    } elseif ($action === 'delete') {

        $id = $_POST['id'] ?? '';

        if ($id !== '') {
            delete_admin_notification($id);
        }

    } elseif ($action === 'clear_all') {

        clear_admin_notifications();
    }

    header("Location: notifications.php");
    exit;
}

$notifications = get_admin_notifications();

$unreadCount = get_admin_unread_count();
$totalCount = count($notifications);

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
?>

<main class="min-w-0 flex-1 lg:ml-64">

    <?php require_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="w-full min-w-0 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Notifications
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Notifications from your customers and system activity.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">

                <?php if ($unreadCount > 0): ?>

                    <form method="POST">
                        <input
                            type="hidden"
                            name="action"
                            value="mark_all_read"
                        >

                        <button
                            type="submit"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            Mark all as read
                        </button>
                    </form>

                <?php endif; ?>

                <?php if ($totalCount > 0): ?>

                    <form method="POST">
                        <input
                            type="hidden"
                            name="action"
                            value="clear_all"
                        >

                        <button
                            type="submit"
                            onclick="return confirm('Clear all notifications?');"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700"
                        >
                            Clear all
                        </button>
                    </form>

                <?php endif; ?>

            </div>

        </div>


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


        <!-- Notifications -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-semibold text-slate-900">
                    Recent Notifications
                </h2>

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

                    <?php foreach (array_reverse($notifications) as $notification): ?>

                        <?php
                        $notificationId = $notification['id'] ?? '';
                        $type = $notification['type'] ?? 'system';
                        $title = $notification['title'] ?? 'Notification';
                        $message = $notification['message'] ?? '';
                        $date = $notification['date'] ?? '';
                        $read = !empty($notification['read']);

                        $icon = admin_notification_icon($type);
                        $style = admin_notification_style($type);
                        ?>

                        <div
                            class="<?= $read ? 'bg-white' : 'bg-indigo-50/40' ?> p-5 transition hover:bg-slate-50"
                        >

                            <div class="flex gap-4">

                                <!-- Icon -->
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl <?= $style ?>"
                                >
                                    <?= $icon ?>
                                </div>


                                <!-- Content -->
                                <div class="min-w-0 flex-1">

                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                                        <div class="min-w-0">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <h3 class="font-semibold text-slate-900">
                                                    <?= notification_escape($title) ?>
                                                </h3>

                                                <?php if (!$read): ?>

                                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                        New
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


                                        <!-- Actions -->
                                        <div class="flex shrink-0 items-center gap-2">

                                            <?php if (!$read): ?>

                                                <form method="POST">

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="mark_read"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= notification_escape($notificationId) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                                    >
                                                        Mark read
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
                                                    name="id"
                                                    value="<?= notification_escape($notificationId) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-red-100 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
                                                >
                                                    Delete
                                                </button>

                                            </form>

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