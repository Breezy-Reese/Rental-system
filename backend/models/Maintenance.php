<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Maintenance
{
    private static function collection()
    {
        return Database::collection('maintenance');
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

        $request = self::collection()->findOne([
            'id' => $id,
        ]);

        return $request
            ? $request->getArrayCopy()
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
            'propertyId' =>
                $data['propertyId'] ?? null,
            'unitId' =>
                $data['unitId'] ?? null,
            'issue' =>
                $data['issue'],
            'description' =>
                $data['description'] ?? '',
            'priority' =>
                $data['priority'] ?? 'Normal',
            'status' =>
                $data['status'] ?? 'Pending',
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

    public static function delete(
        string $id
    ): bool {

        $result = self::collection()->deleteOne([
            'id' => $id,
        ]);

        return $result->getDeletedCount() > 0;
    }
}