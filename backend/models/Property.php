<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Property
{
    private static function collection()
    {
        return Database::collection('properties');
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

        $property = self::collection()->findOne([
            'id' => $id,
        ]);

        return $property
            ? $property->getArrayCopy()
            : null;
    }

    public static function create(
        array $data
    ): string {

        $document = [
            'id' => $data['id'],
            'name' => $data['name'],
            'location' => $data['location'] ?? '',
            'address' => $data['address'] ?? '',
            'description' => $data['description'] ?? '',
            'totalUnits' => (int) (
                $data['totalUnits'] ?? 0
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

    public static function delete(
        string $id
    ): bool {

        $result = self::collection()->deleteOne([
            'id' => $id,
        ]);

        return $result->getDeletedCount() > 0;
    }
}