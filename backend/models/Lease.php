<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Lease
{
    private static function collection()
    {
        return Database::collection('leases');
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

        $lease = self::collection()->findOne([
            'id' => $id,
        ]);

        return $lease
            ? $lease->getArrayCopy()
            : null;
    }

    public static function findByTenant(
        string $tenantId
    ): array {

        return self::collection()
            ->find([
                'tenantId' => $tenantId,
            ])
            ->toArray();
    }

    public static function create(
        array $data
    ): string {

        $document = [
            'id' => $data['id'],
            'tenantId' => $data['tenantId'],
            'propertyId' => $data['propertyId'],
            'unitId' => $data['unitId'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'] ?? null,
            'rent' => (float) (
                $data['rent'] ?? 0
            ),
            'deposit' => (float) (
                $data['deposit'] ?? 0
            ),
            'status' => $data['status'] ?? 'Active',
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