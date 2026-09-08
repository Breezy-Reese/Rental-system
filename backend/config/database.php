<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Database;

final class Database
{
    private static ?Client $client = null;
    private static ?Database $database = null;

    public static function connect(): Database
    {
        if (self::$database !== null) {
            return self::$database;
        }

        $uri = getenv('MONGODB_URI');

        if (!$uri && isset($_ENV['MONGODB_URI'])) {
            $uri = $_ENV['MONGODB_URI'];
        }

        if (!$uri) {
            $uri = 'mongodb://127.0.0.1:27017';
        }

        $databaseName = getenv('MONGODB_DATABASE');

        if (!$databaseName && isset($_ENV['MONGODB_DATABASE'])) {
            $databaseName = $_ENV['MONGODB_DATABASE'];
        }

        if (!$databaseName) {
            $databaseName = 'propertypro';
        }

        self::$client = new Client($uri);

        self::$database = self::$client->selectDatabase(
            $databaseName
        );

        return self::$database;
    }

    public static function collection(string $name)
    {
        return self::connect()->selectCollection($name);
    }
}