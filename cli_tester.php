<?php

require 'vendor/autoload.php';

use Robinson\InterestAccount\Application\AccountServiceFactory;

$service = AccountServiceFactory::create();
$userId = '550e8400-e29b-41d4-a716-446655440000';

try {
    $accountId = $service->openAccount($userId);
    echo "Account opened! ID: $accountId\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Example: £1000.00 = 10000 pennies
$service->depositForUser($userId, $accountId, 100000);

// This is typically run via a cron job (e.g., every 3 days).
for ($i = 1; $i <= 9; $i++) {
    if ($i % 3 === 0) {
        $service->calculateInterest($accountId, new DateTimeImmutable("+$i day"));
    }
}

$statement = $service->getStatement($accountId);
echo 'Statement: ' . PHP_EOL;
print_r($statement);