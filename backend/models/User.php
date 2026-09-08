<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use MongoDB\BSON\ObjectId;

final class User
{
    private static function collection()
    {
        return Database::collection('users');
    }

    public static function findByEmail(string $email): ?array
    {
        $user = self::collection()->findOne([
            'email' => strtolower(trim($email)),
        ]);

        return $user ? $user->getArrayCopy() : null;
    }

    public static function findById(string $id): ?array
    {
        $user = self::collection()->findOne([
            'id' => $id,
        ]);

        return $user ? $user->getArrayCopy() : null;
    }

    public static function create(array $data): string
    {
        $document = [
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            ),
            'role' => $data['role'] ?? 'Customer',
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? 'Active',
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime(),
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

    public static function verifyPassword(
        array $user,
        string $password
    ): bool {

        return isset($user['password']) &&
            password_verify(
                $password,
                $user['password']
            );
    }
}