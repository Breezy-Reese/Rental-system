<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/functions.php';

final class PaymentController
{
    public static function index(): never
    {
        try {
            success_response(Payment::all());
        } catch (Throwable $e) {
            error_response($e->getMessage(), 500);
        }
    }

    public static function show(string $id): never
    {
        $payment = Payment::findById($id);

        if (!$payment) {
            error_response('Payment not found.', 404);
        }

        success_response($payment);
    }

    public static function byTenant(string $tenantId): never
    {
        try {
            success_response(
                Payment::findByTenant($tenantId)
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 500);
        }
    }

    public static function store(): never
    {
        try {
            $data = request_data();

            if (empty($data['tenantId'])) {
                error_response(
                    'Tenant is required.',
                    422
                );
            }

            if (
                !isset($data['amount']) ||
                !is_numeric($data['amount']) ||
                (float) $data['amount'] <= 0
            ) {
                error_response(
                    'A valid payment amount is required.',
                    422
                );
            }

            $tenant = Tenant::findById(
                $data['tenantId']
            );

            if (!$tenant) {
                error_response(
                    'Tenant not found.',
                    404
                );
            }

            $paymentId = generate_id('PAY');

            Payment::create([
                'id' => $paymentId,
                'tenantId' => $data['tenantId'],
                'leaseId' => $data['leaseId'] ?? null,
                'amount' => (float) $data['amount'],
                'paymentMethod' =>
                    $data['paymentMethod'] ?? 'M-Pesa',
                'reference' =>
                    $data['reference'] ?? null,
                'status' =>
                    $data['status'] ?? 'Pending',
                'paymentDate' =>
                    $data['paymentDate'] ?? date('Y-m-d'),
            ]);

            /*
             * Notify administrator when a payment is created.
             */
            $customerId =
                $tenant['userId']
                ?? $data['customerId']
                ?? $tenant['id'];

            NotificationService::paymentCreated(
                (string) $customerId,
                (string) ($tenant['name'] ?? 'Customer'),
                $paymentId,
                (float) $data['amount'],
                (string) (
                    $data['paymentMethod'] ?? 'M-Pesa'
                )
            );

            success_response(
                ['id' => $paymentId],
                'Payment recorded successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }

    public static function update(string $id): never
    {
        try {
            $payment = Payment::findById($id);

            if (!$payment) {
                error_response(
                    'Payment not found.',
                    404
                );
            }

            $data = request_data();

            unset(
                $data['id'],
                $data['_id'],
                $data['createdAt']
            );

            Payment::update($id, $data);

            /*
             * If payment status changed, notify customer.
             */
            if (
                isset($data['status']) &&
                isset($payment['tenantId'])
            ) {
                $tenant = Tenant::findById(
                    (string) $payment['tenantId']
                );

                if (
                    $tenant &&
                    !empty($tenant['userId'])
                ) {
                    NotificationService::paymentStatusUpdated(
                        (string) $tenant['userId'],
                        $id,
                        (string) $data['status']
                    );
                }
            }

            success_response(
                null,
                'Payment updated successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }
}