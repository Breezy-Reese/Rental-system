<?php

require_once __DIR__ . "/auth.php";

/**
 * Create a notification for the administrator.
 */
function add_admin_notification(
    string $type,
    string $title,
    string $message,
    array $data = []
): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['admin_notifications'])) {
        $_SESSION['admin_notifications'] = [];
    }

    $_SESSION['admin_notifications'][] = [
        'id' => 'ADMIN-NOT-' . uniqid(),
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'date' => date('Y-m-d H:i:s'),
        'read' => false,
        'data' => $data,
    ];
}

/**
 * Get all admin notifications.
 */
function get_admin_notifications(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return $_SESSION['admin_notifications'] ?? [];
}

/**
 * Get unread admin notification count.
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

/**
 * Mark one admin notification as read.
 */
function mark_admin_notification_read(string $id): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['admin_notifications'])) {
        return;
    }

    foreach ($_SESSION['admin_notifications'] as &$notification) {
        if (($notification['id'] ?? '') === $id) {
            $notification['read'] = true;
            break;
        }
    }

    unset($notification);
}

/**
 * Mark all admin notifications as read.
 */
function mark_all_admin_notifications_read(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['admin_notifications'])) {
        return;
    }

    foreach ($_SESSION['admin_notifications'] as &$notification) {
        $notification['read'] = true;
    }

    unset($notification);
}

/**
 * Delete one admin notification.
 */
function delete_admin_notification(string $id): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['admin_notifications'])) {
        return;
    }

    $_SESSION['admin_notifications'] = array_values(
        array_filter(
            $_SESSION['admin_notifications'],
            function ($notification) use ($id) {
                return ($notification['id'] ?? '') !== $id;
            }
        )
    );
}

/**
 * Clear all admin notifications.
 */
function clear_admin_notifications(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['admin_notifications'] = [];
}

/**
 * Escape notification output.
 */
function notification_escape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Notification icon.
 */
function admin_notification_icon(string $type): string
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

/**
 * Notification background.
 */
function admin_notification_style(string $type): string
{
    return match ($type) {
        'maintenance' => 'bg-orange-100 text-orange-600',
        'payment' => 'bg-green-100 text-green-600',
        'lease' => 'bg-blue-100 text-blue-600',
        'profile' => 'bg-purple-100 text-purple-600',
        'message' => 'bg-indigo-100 text-indigo-600',
        default => 'bg-slate-100 text-slate-600',
    };
}

/**
 * Human-readable notification time.
 */
function admin_notification_time(string $date): string
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
        return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
    }

    if ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }

    if ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return date('d M Y, H:i', $timestamp);
}