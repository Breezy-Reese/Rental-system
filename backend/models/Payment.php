<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Payment
{
    private static function collection()
    {
        return Database::collection('payments');
    }

    public static function all(): array
    {
        return self::collection()
            ->find([], [
                'sort' => ['createdAt' => -1],
            ])
            ->toArray();
    }

    public static function findById(
        string $id
    ): ?array {

        $payment = self::collection()->findOne([
            'id' => $id,
        ]);

        return $payment
            ? $payment->getArrayCopy()
            : null;
    }

    public static function findByTenant(
        string $tenantId
    ): array {

        return self::collection()
            ->find([
                'tenantId' => $tenantId,
            ], [
                'sort' => [
                    'createdAt' => -1,
                ],
            ])
            ->toArray();
    }

    public static function create(
        array $data
    ): string {

        $document = [
            'id' => $data['id'],
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
            'createdAt' =>
                new MongoDB\BSON\UTCDateTime(),
            'updatedAt' =>
                new MongoDB\BSON\UTCDateTime(),
        ];

        self::collection()->insertOne($document);

        return $document['id'];
    }

    public static function update(
        string $id,
        array $data
    ): bool {

        $data['updatedAt'] =
            new MongoDB\BSON\UTCDateTime();

        $result = self::collection()->updateOne(
            ['id' => $id],
            ['$set' => $data]
        );

        return $result->getModifiedCount() > 0;
    }
}