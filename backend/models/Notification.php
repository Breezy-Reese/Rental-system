<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Notification
{
    private static function collection()
    {
        return Database::collection('notifications');
    }

    public static function create(
        string $recipientRole,
        string $type,
        string $title,
        string $message,
        ?string $recipientId = null,
        array $data = []
    ): string {

        $id = generate_id('NOT');

        $document = [
            'id' => $id,
            'recipientRole' => $recipientRole,
            'recipientId' => $recipientId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read' => false,
            'createdAt' =>
                new MongoDB\BSON\UTCDateTime(),
            'updatedAt' =>
                new MongoDB\BSON\UTCDateTime(),
        ];

        self::collection()->insertOne($document);

        return $id;
    }

    public static function adminNotifications(): array
    {
        return self::collection()
            ->find([
                'recipientRole' => 'Administrator',
            ], [
                'sort' => [
                    'createdAt' => -1,
                ],
            ])
            ->toArray();
    }

    public static function customerNotifications(
        string $customerId
    ): array {

        return self::collection()
            ->find([
                'recipientRole' => 'Customer',
                'recipientId' => $customerId,
            ], [
                'sort' => [
                    'createdAt' => -1,
                ],
            ])
            ->toArray();
    }

    public static function adminUnreadCount(): int
    {
        return self::collection()->countDocuments([
            'recipientRole' => 'Administrator',
            'read' => false,
        ]);
    }

    public static function customerUnreadCount(
        string $customerId
    ): int {

        return self::collection()->countDocuments([
            'recipientRole' => 'Customer',
            'recipientId' => $customerId,
            'read' => false,
        ]);
    }

    public static function markRead(
        string $id
    ): bool {

        $result = self::collection()->updateOne(
            ['id' => $id],
            [
                '$set' => [
                    'read' => true,
                    'updatedAt' =>
                        new MongoDB\BSON\UTCDateTime(),
                ],
            ]
        );

        return $result->getModifiedCount() > 0;
    }

    public static function markAllAdminRead(): void
    {
        self::collection()->updateMany(
            [
                'recipientRole' => 'Administrator',
                'read' => false,
            ],
            [
                '$set' => [
                    'read' => true,
                    'updatedAt' =>
                        new MongoDB\BSON\UTCDateTime(),
                ],
            ]
        );
    }

    public static function markAllCustomerRead(
        string $customerId
    ): void {

        self::collection()->updateMany(
            [
                'recipientRole' => 'Customer',
                'recipientId' => $customerId,
                'read' => false,
            ],
            [
                '$set' => [
                    'read' => true,
                    'updatedAt' =>
                        new MongoDB\BSON\UTCDateTime(),
                ],
            ]
        );
    }

    public static function delete(
        string $id
    ): bool {

        $result = self::collection()->deleteOne([
            'id' => $id,
        ]);

        return $result->getDeletedCount() > 0;
    }

    public static function clearAdmin(): void
    {
        self::collection()->deleteMany([
            'recipientRole' => 'Administrator',
        ]);
    }
}