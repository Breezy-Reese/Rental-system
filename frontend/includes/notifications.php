<?php

/*
|--------------------------------------------------------------------------
| PropertyPro Notification Backend
|--------------------------------------------------------------------------
| Shared notification storage for Administrator and Customer.
|
| Storage:
|     /storage/notifications.json
|
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Notification Storage
|--------------------------------------------------------------------------
*/

function notification_storage_file(): string
{
    return dirname(__DIR__) . '/storage/notifications.json';
}


/*
|--------------------------------------------------------------------------
| Make sure storage directory/file exists
|--------------------------------------------------------------------------
*/

function ensure_notification_storage(): void
{
    $storageDirectory = dirname(notification_storage_file());

    if (!is_dir($storageDirectory)) {
        mkdir($storageDirectory, 0775, true);
    }

    $file = notification_storage_file();

    if (!file_exists($file)) {
        file_put_contents(
            $file,
            json_encode(
                [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );
    }
}


/*
|--------------------------------------------------------------------------
| Read Notifications
|--------------------------------------------------------------------------
*/

function get_all_notifications(): array
{
    ensure_notification_storage();

    $file = notification_storage_file();

    $contents = file_get_contents($file);

    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $notifications = json_decode($contents, true);

    if (!is_array($notifications)) {
        return [];
    }

    return $notifications;
}


/*
|--------------------------------------------------------------------------
| Save Notifications
|--------------------------------------------------------------------------
*/

function save_all_notifications(array $notifications): bool
{
    ensure_notification_storage();

    $json = json_encode(
        array_values($notifications),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    if ($json === false) {
        return false;
    }

    return file_put_contents(
        notification_storage_file(),
        $json,
        LOCK_EX
    ) !== false;
}


/*
|--------------------------------------------------------------------------
| Create Notification
|--------------------------------------------------------------------------
*/

function create_notification(
    string $recipientRole,
    string $type,
    string $title,
    string $message,
    array $data = [],
    ?string $recipientId = null
): array {

    $notifications = get_all_notifications();

    $notification = [
        'id' => 'NOT-' . strtoupper(bin2hex(random_bytes(5))),

        'recipient_role' => $recipientRole,

        'recipient_id' => $recipientId,

        'type' => $type,

        'title' => $title,

        'message' => $message,

        'data' => $data,

        'read' => false,

        'created_at' => date('Y-m-d H:i:s'),
    ];

    $notifications[] = $notification;

    save_all_notifications($notifications);

    return $notification;
}


/*
|--------------------------------------------------------------------------
| Create Administrator Notification
|--------------------------------------------------------------------------
*/

function add_admin_notification(
    string $type,
    string $title,
    string $message,
    array $data = []
): array {

    return create_notification(
        'Administrator',
        $type,
        $title,
        $message,
        $data
    );
}


/*
|--------------------------------------------------------------------------
| Create Customer Notification
|--------------------------------------------------------------------------
*/

function add_customer_notification(
    string $customerId,
    string $type,
    string $title,
    string $message,
    array $data = []
): array {

    return create_notification(
        'Customer',
        $type,
        $title,
        $message,
        $data,
        $customerId
    );
}


/*
|--------------------------------------------------------------------------
| Get Administrator Notifications
|--------------------------------------------------------------------------
*/

function get_admin_notifications(): array
{
    $notifications = get_all_notifications();

    return array_values(
        array_filter(
            $notifications,
            function ($notification) {

                return ($notification['recipient_role'] ?? '') === 'Administrator';
            }
        )
    );
}


/*
|--------------------------------------------------------------------------
| Get Customer Notifications
|--------------------------------------------------------------------------
*/

function get_customer_notifications(string $customerId): array
{
    $notifications = get_all_notifications();

    return array_values(
        array_filter(
            $notifications,
            function ($notification) use ($customerId) {

                return
                    ($notification['recipient_role'] ?? '') === 'Customer'
                    &&
                    ($notification['recipient_id'] ?? '') === $customerId;
            }
        )
    );
}


/*
|--------------------------------------------------------------------------
| Admin Unread Count
|--------------------------------------------------------------------------
*/

function get_admin_unread_count(): int
{
    $notifications = get_admin_notifications();

    $count = 0;

    foreach ($notifications as $notification) {

        if (empty($notification['read'])) {
            $count++;
        }
    }

    return $count;
}


/*
|--------------------------------------------------------------------------
| Customer Unread Count
|--------------------------------------------------------------------------
*/

function get_customer_unread_count(string $customerId): int
{
    $notifications = get_customer_notifications($customerId);

    $count = 0;

    foreach ($notifications as $notification) {

        if (empty($notification['read'])) {
            $count++;
        }
    }

    return $count;
}


/*
|--------------------------------------------------------------------------
| Mark Notification As Read
|--------------------------------------------------------------------------
*/

function mark_notification_read(string $notificationId): bool
{
    $notifications = get_all_notifications();

    $updated = false;

    foreach ($notifications as &$notification) {

        if (($notification['id'] ?? '') === $notificationId) {

            $notification['read'] = true;

            $updated = true;

            break;
        }
    }

    unset($notification);

    if ($updated) {
        return save_all_notifications($notifications);
    }

    return false;
}


/*
|--------------------------------------------------------------------------
| Mark All Admin Notifications As Read
|--------------------------------------------------------------------------
*/

function mark_all_admin_notifications_read(): bool
{
    $notifications = get_all_notifications();

    $updated = false;

    foreach ($notifications as &$notification) {

        if (
            ($notification['recipient_role'] ?? '') === 'Administrator'
            &&
            empty($notification['read'])
        ) {

            $notification['read'] = true;

            $updated = true;
        }
    }

    unset($notification);

    if ($updated) {
        return save_all_notifications($notifications);
    }

    return true;
}


/*
|--------------------------------------------------------------------------
| Mark All Customer Notifications As Read
|--------------------------------------------------------------------------
*/

function mark_all_customer_notifications_read(
    string $customerId
): bool {

    $notifications = get_all_notifications();

    $updated = false;

    foreach ($notifications as &$notification) {

        if (
            ($notification['recipient_role'] ?? '') === 'Customer'
            &&
            ($notification['recipient_id'] ?? '') === $customerId
            &&
            empty($notification['read'])
        ) {

            $notification['read'] = true;

            $updated = true;
        }
    }

    unset($notification);

    if ($updated) {
        return save_all_notifications($notifications);
    }

    return true;
}


/*
|--------------------------------------------------------------------------
| Delete Notification
|--------------------------------------------------------------------------
*/

function delete_notification(string $notificationId): bool
{
    $notifications = get_all_notifications();

    $originalCount = count($notifications);

    $notifications = array_values(
        array_filter(
            $notifications,
            function ($notification) use ($notificationId) {

                return ($notification['id'] ?? '') !== $notificationId;
            }
        )
    );

    if (count($notifications) === $originalCount) {
        return false;
    }

    return save_all_notifications($notifications);
}


/*
|--------------------------------------------------------------------------
| Clear All Admin Notifications
|--------------------------------------------------------------------------
*/

function clear_admin_notifications(): bool
{
    $notifications = get_all_notifications();

    $notifications = array_values(
        array_filter(
            $notifications,
            function ($notification) {

                return ($notification['recipient_role'] ?? '') !== 'Administrator';
            }
        )
    );

    return save_all_notifications($notifications);
}


/*
|--------------------------------------------------------------------------
| Clear Customer Notifications
|--------------------------------------------------------------------------
*/

function clear_customer_notifications(string $customerId): bool
{
    $notifications = get_all_notifications();

    $notifications = array_values(
        array_filter(
            $notifications,
            function ($notification) use ($customerId) {

                return !(
                    ($notification['recipient_role'] ?? '') === 'Customer'
                    &&
                    ($notification['recipient_id'] ?? '') === $customerId
                );
            }
        )
    );

    return save_all_notifications($notifications);
}


/*
|--------------------------------------------------------------------------
| Escape Notification Text
|--------------------------------------------------------------------------
*/

function notification_escape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Notification Icon
|--------------------------------------------------------------------------
*/

function notification_icon(string $type): string
{
    return match ($type) {

        'maintenance' => '🔧',

        'payment' => '💰',

        'lease' => '📄',

        'profile' => '👤',

        'message' => '💬',

        'system' => '⚙️',

        default => '🔔',
    };
}


/*
|--------------------------------------------------------------------------
| Notification Style
|--------------------------------------------------------------------------
*/

function notification_style(string $type): string
{
    return match ($type) {

        'maintenance' =>
            'bg-orange-100 text-orange-600',

        'payment' =>
            'bg-green-100 text-green-600',

        'lease' =>
            'bg-blue-100 text-blue-600',

        'profile' =>
            'bg-purple-100 text-purple-600',

        'message' =>
            'bg-indigo-100 text-indigo-600',

        default =>
            'bg-slate-100 text-slate-600',
    };
}


/*
|--------------------------------------------------------------------------
| Notification Time
|--------------------------------------------------------------------------
*/

function notification_time(string $date): string
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return $date;
    }

    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'Just now';
    }

    if ($difference < 3600) {

        $minutes = floor($difference / 60);

        return $minutes . ' minute' .
            ($minutes === 1 ? '' : 's') . ' ago';
    }

    if ($difference < 86400) {

        $hours = floor($difference / 3600);

        return $hours . ' hour' .
            ($hours === 1 ? '' : 's') . ' ago';
    }

    if ($difference < 604800) {

        $days = floor($difference / 86400);

        return $days . ' day' .
            ($days === 1 ? '' : 's') . ' ago';
    }

    return date(
        'd M Y, H:i',
        $timestamp
    );
}