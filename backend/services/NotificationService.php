<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Notification.php';

final class NotificationService
{
    public static function notifyAdmin(
        string $type,
        string $title,
        string $message,
        array $data = []
    ): string {
        return Notification::create(
            'Administrator',
            $type,
            $title,
            $message,
            null,
            $data
        );
    }

    public static function notifyCustomer(
        string $customerId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): string {
        return Notification::create(
            'Customer',
            $type,
            $title,
            $message,
            $customerId,
            $data
        );
    }

    public static function maintenanceCreated(
        string $customerId,
        string $customerName,
        string $requestId,
        string $issue,
        string $property,
        string $unit,
        string $priority
    ): string {

        return self::notifyAdmin(
            'maintenance',
            'New Maintenance Request',
            $customerName .
                ' submitted a new maintenance request: ' .
                $issue,
            [
                'requestId' => $requestId,
                'customerId' => $customerId,
                'customerName' => $customerName,
                'property' => $property,
                'unit' => $unit,
                'priority' => $priority,
            ]
        );
    }

    public static function paymentCreated(
        string $customerId,
        string $customerName,
        string $paymentId,
        float $amount,
        string $paymentMethod
    ): string {

        return self::notifyAdmin(
            'payment',
            'New Payment Received',
            $customerName .
                ' made a payment of ' .
                money($amount),
            [
                'paymentId' => $paymentId,
                'customerId' => $customerId,
                'customerName' => $customerName,
                'amount' => $amount,
                'paymentMethod' => $paymentMethod,
            ]
        );
    }

    public static function leaseCreated(
        string $customerId,
        string $customerName,
        string $leaseId,
        string $property,
        string $unit
    ): string {

        return self::notifyAdmin(
            'lease',
            'New Lease Request',
            $customerName .
                ' submitted a new lease request.',
            [
                'leaseId' => $leaseId,
                'customerId' => $customerId,
                'customerName' => $customerName,
                'property' => $property,
                'unit' => $unit,
            ]
        );
    }

    public static function profileUpdated(
        string $customerId,
        string $customerName
    ): string {

        return self::notifyAdmin(
            'profile',
            'Customer Profile Updated',
            $customerName .
                ' updated their profile information.',
            [
                'customerId' => $customerId,
                'customerName' => $customerName,
            ]
        );
    }

    public static function maintenanceStatusUpdated(
        string $customerId,
        string $requestId,
        string $status
    ): string {

        return self::notifyCustomer(
            $customerId,
            'maintenance',
            'Maintenance Request Updated',
            'Your maintenance request ' .
                $requestId .
                ' is now ' .
                $status . '.',
            [
                'requestId' => $requestId,
                'status' => $status,
            ]
        );
    }

    public static function paymentStatusUpdated(
        string $customerId,
        string $paymentId,
        string $status
    ): string {

        return self::notifyCustomer(
            $customerId,
            'payment',
            'Payment Status Updated',
            'Your payment ' .
                $paymentId .
                ' is now ' .
                $status . '.',
            [
                'paymentId' => $paymentId,
                'status' => $status,
            ]
        );
    }
}