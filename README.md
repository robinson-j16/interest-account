# Interest Account Library

A PHP library for managing user interest accounts.

This library models an **Interest Account** domain, providing:

- **Account Management**: Opening unique interest accounts for users (UUIDv4).
- **Compliance-Ready Math**: Uses integer-based "micro-penny" calculations to eliminate floating-point errors.
- **Dynamic Interest Rates**: Integrates with external User Stats APIs to determine rates based on income.
- **Transaction History**: Maintains an audit log of all deposits and interest payouts.
- **Idempotent Scheduling**: Safe to run on any schedule (e.g., every 3 days) without double-paying or missing interest.

---

## 🧪 Testing

The project includes a Dockerized test environment to ensure consistency.

### Build and Install Dependencies

```bash
make install
```

### Run Unit Tests

```bash
make test
```

### Run Code Quality Checks (Linting + Tests)

```bash
make qa
```

---

## 🚀 Quick Start (Using the Factory)

The easiest way to get started is using the `AccountServiceFactory`. This automatically wires up the default infrastructure (in-memory storage, Ramsey UUIDs, and Guzzle HTTP client).

```php
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
}

// Deposit Funds (Amount in Pennies)
// Example: £100.00 = 10000 pennies
$service->depositForUser($userId, $accountId, 10000);

// Run Interest Calculation
// This is typically run via a cron job (e.g., every 3 days).
$service->calculateInterest($accountId);

$statement = $service->getStatement($accountId);
print_r($statement);
```

---

## 🏗 Architecture

This library follows a Hexagonal Architecture.

- **Service Layer (`AccountService`)**: Contains the business logic orchestration.
- **Domain Model (`Account`)**: Encapsulates state rules (e.g., never extracting < 1 penny).
- **Infrastructure (`src/Infrastructure`)**: Contains implementations for storage and APIs.

---
