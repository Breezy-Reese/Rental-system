<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/response.php';

final class NotificationController
{
    public static function admin(): never
    {
        try {
            success_response(
                Notification::adminNotifications()
            );
        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                500
            );
        }
    }

    public static function customer(
        string $customerId
    ): never {

        try {
            success_response(
                Notification::customerNotifications(
                    $customerId
                )
            );
        } catch (Throwable $e) {
            error_response(
                $e->getMessage(),
                500
            );
        }
    }

    public static function adminUnread(): never
    {
        success_response([
            'count' =>
                Notification::adminUnreadCount(),
        ]);
    }

    public static function customerUnread(
        string $customerId
    ): never {

        success_response([
            'count' =>
                Notification::customerUnreadCount(
                    $customerId
                ),
        ]);
    }

    public static function markRead(
        string $id
    ): never {

        if (!Notification::markRead($id)) {
            error_response(
                'Notification not found.',
                404
            );
        }

        success_response(
            null,
            'Notification marked as read.'
        );
    }

    public static function markAllAdminRead(): never
    {
        Notification::markAllAdminRead();

        success_response(
            null,
            'All administrator notifications marked as read.'
        );
    }

    public static function markAllCustomerRead(
        string $customerId
    ): never {

        Notification::markAllCustomerRead(
            $customerId
        );

        success_response(
            null,
            'All customer notifications marked as read.'
        );
    }

    public static function delete(
        string $id
    ): never {

        if (!Notification::delete($id)) {
            error_response(
                'Notification not found.',
                404
            );
        }

        success_response(
            null,
            'Notification deleted successfully.'
        );
    }

    public static function clearAdmin(): never
    {
        Notification::clearAdmin();

        success_response(
            null,
            'Administrator notifications cleared.'
        );
    }
}