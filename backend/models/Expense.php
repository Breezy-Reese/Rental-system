<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Expense
{
    private static function collection()
    {
        return Database::collection('expenses');
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

        $expense = self::collection()->findOne([
            'id' => $id,
        ]);

        return $expense
            ? $expense->getArrayCopy()
            : null;
    }

    public static function create(
        array $data
    ): string {

        $document = [
            'id' => $data['id'],
            'propertyId' =>
                $data['propertyId'] ?? null,
            'category' =>
                $data['category'] ?? 'General',
            'description' =>
                $data['description'],
            'amount' =>
                (float) $data['amount'],
            'expenseDate' =>
                $data['expenseDate'] ??
                date('Y-m-d'),
            'status' =>
                $data['status'] ?? 'Recorded',
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