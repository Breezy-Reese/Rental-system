<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/../helpers/functions.php';

final class PaymentService
{
    public static function createPayment(
        string $tenantId,
        float $amount,
        string $paymentMethod,
        ?string $leaseId = null,
        ?string $reference = null,
        ?string $paymentDate = null
    ): string {

        $paymentId = generate_id('PAY');

        Payment::create([
            'id' => $paymentId,
            'tenantId' => $tenantId,
            'leaseId' => $leaseId,
            'amount' => $amount,
            'paymentMethod' => $paymentMethod,
            'reference' => $reference,
            'status' => 'Pending',
            'paymentDate' =>
                $paymentDate ?? date('Y-m-d'),
        ]);

        return $paymentId;
    }

    public static function markPaid(
        string $paymentId,
        ?string $reference = null
    ): bool {

        $payment = Payment::findById($paymentId);

        if (!$payment) {
            return false;
        }

        $data = [
            'status' => 'Paid',
        ];

        if ($reference !== null) {
            $data['reference'] = $reference;
        }

        return Payment::update(
            $paymentId,
            $data
        );
    }

    public static function markFailed(
        string $paymentId
    ): bool {

        return Payment::update(
            $paymentId,
            [
                'status' => 'Failed',
            ]
        );
    }
}