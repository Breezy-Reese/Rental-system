<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Tenant
{
    private static function collection()
    {
        return Database::collection('tenants');
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

        $tenant = self::collection()->findOne([
            'id' => $id,
        ]);

        return $tenant
            ? $tenant->getArrayCopy()
            : null;
    }

    public static function findByUserId(
        string $userId
    ): ?array {

        $tenant = self::collection()->findOne([
            'userId' => $userId,
        ]);

        return $tenant
            ? $tenant->getArrayCopy()
            : null;
    }

    public static function create(
        array $data
    ): string {

        $document = [
            'id' => $data['id'],
            'userId' => $data['userId'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'propertyId' => $data['propertyId'] ?? null,
            'unitId' => $data['unitId'] ?? null,
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