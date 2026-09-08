<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Unit
{
    private static function collection()
    {
        return Database::collection('units');
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

        $unit = self::collection()->findOne([
            'id' => $id,
        ]);

        return $unit
            ? $unit->getArrayCopy()
            : null;
    }

    public static function findByProperty(
        string $propertyId
    ): array {

        return self::collection()
            ->find([
                'propertyId' => $propertyId,
            ])
            ->toArray();
    }

    public static function create(
        array $data
    ): string {

        $document = [
            'id' => $data['id'],
            'propertyId' => $data['propertyId'],
            'unitNumber' => $data['unitNumber'],
            'type' => $data['type'] ?? 'Standard',
            'rent' => (float) (
                $data['rent'] ?? 0
            ),
            'status' => $data['status'] ?? 'Vacant',
            'tenantId' => $data['tenantId'] ?? null,
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