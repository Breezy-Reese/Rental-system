<?php

require_once __DIR__ . "/../../includes/admin.php";
require_admin();

require_once __DIR__ . "/../../includes/data.php";

$pageTitle = "Notifications";

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";

/*
|--------------------------------------------------------------------------
| Notification data comes directly from data.php
|--------------------------------------------------------------------------
*/

$notifications = $notifications ?? [];

$totalCount = count($notifications);

$unreadCount = 0;

foreach ($notifications as $notification) {
    if (empty($notification['read'])) {
        $unreadCount++;
    }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('notification_escape')) {
    function notification_escape($value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('admin_notification_icon')) {
    function admin_notification_icon(string $type): string
    {
        return match ($type) {
            'payment_created',
            'payment_status_changed' => '💰',

            'maintenance_created',
            'maintenance_status_changed' => '🔧',

            'expense_created' => '💳',

            default => '🔔',
        };
    }
}

if (!function_exists('admin_notification_style')) {
    function admin_notification_style(string $type): string
    {
        return match ($type) {
            'payment_created',
            'payment_status_changed'
                => 'bg-green-100 text-green-700',

            'maintenance_created',
            'maintenance_status_changed'
                => 'bg-amber-100 text-amber-700',

            'expense_created'
                => 'bg-red-100 text-red-700',

            default
                => 'bg-indigo-100 text-indigo-700',
        };
    }
}

if (!function_exists('admin_notification_time')) {
    function admin_notification_time($date): string
    {
        if (empty($date)) {
            return '';
        }

        $timestamp = strtotime((string) $date);

        if ($timestamp === false) {
            return (string) $date;
        }

        return date('d M Y, H:i', $timestamp);
    }
}

?>

<main class="min-w-0 flex-1 lg:ml-64">

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

                    <?php foreach ($notifications as $notification): ?>

                        <?php

                        $notificationId =
                            $notification['id']
                            ?? $notification['_id']
                            ?? '';

                        $type =
                            $notification['type']
                            ?? 'system';

                        $title =
                            $notification['title']
                            ?? 'Notification';

                        $message =
                            $notification['message']
                            ?? '';

                        $date =
                            $notification['date']
                            ?? '';

                        $read =
                            !empty($notification['read']);

                        $icon =
                            admin_notification_icon($type);

                        $style =
                            admin_notification_style($type);

                        ?>

                        <div
                            class="<?= $read
                                ? 'bg-white'
                                : 'bg-indigo-50/40'
                            ?> p-5 transition hover:bg-slate-50"
                        >

                            <div class="flex gap-4">

                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl <?= $style ?>"
                                >
                                    <?= $icon ?>
                                </div>


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