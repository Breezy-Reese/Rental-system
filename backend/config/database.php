<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Database;

$dotenv = Dotenv\Dotenv::createImmutable(
    dirname(__DIR__, 2)
);

$dotenv->safeLoad();

final class Database
{
    private static ?Client $client = null;
    private static ?Database $database = null;

    public static function connect(): Database
    {
        if (self::$database !== null) {
            return self::$database;
        }

        $uri = $_ENV['MONGODB_URI'] ?? '';

        if ($uri === '') {
            throw new RuntimeException(
                'MONGODB_URI is not configured.'
            );
        }

        $databaseName =
            $_ENV['MONGODB_DATABASE']
            ?? 'Rental';

        self::$client = new Client($uri);

        self::$database =
            self::$client->selectDatabase(
                $databaseName
            );

        return self::$database;
    }

    public static function collection(string $name)
    {
        return self::connect()
            ->selectCollection($name);
    }
}