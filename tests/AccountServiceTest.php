<?php

declare(strict_types=1);

namespace Robinson\InterestAccount\Tests;

use Robinson\InterestAccount\Infrastructure\InMemoryAccountRepository;
use Robinson\InterestAccount\Infrastructure\InMemoryTransactionRepository;
use Robinson\InterestAccount\Infrastructure\RamseyUuidGenerator;
use Robinson\InterestAccount\Infrastructure\StatsApiClient;
use Robinson\InterestAccount\Repository\DefaultRatePolicy;
use Robinson\InterestAccount\Service\AccountService;
use Robinson\InterestAccount\Service\InterestCalculator;
use DateTimeImmutable;
use DateInterval;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class AccountServiceTest extends TestCase
{
    private DefaultRatePolicy $policy;
    public function setUp(): void
    {
        parent::setUp();
        $this->policy = PolicyFactory::default();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    private function createServiceWithMock(
        ClientInterface $mockHttp,
        ?InMemoryAccountRepository &$accountRepo = null,
        ?InMemoryTransactionRepository &$txnRepo = null
    ): AccountService {
        $statsClient = new StatsApiClient($mockHttp, $this->policy);
        $accountRepo = new InMemoryAccountRepository();
        $txnRepo = new InMemoryTransactionRepository();

        return new AccountService(
            $accountRepo,
            $txnRepo,
            $statsClient,
            new InterestCalculator(),
            new RamseyUuidGenerator(),
        );
    }

    public function testOpenAccountSetsCorrectRateForHighIncome(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 600000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $accId = $service->openAccount(Uuid::uuid4()->toString());

        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(1.02, $account->getInterestRate());
    }

    public function testOpenAccountRejectsInvalidUuid(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->never();

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID');
        $service->openAccount('not-a-uuid');
    }

    public function testOpenAccountAcceptsValidUuidV4(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);

        $this->assertIsString($accId);
        $this->assertNotNull($accountRepo->get($accId));
    }

    public function testOpenAccountSetsCorrectRateForLowIncome(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 4_999_99])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $accId = $service->openAccount(Uuid::uuid4()->toString());
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(0.93, $account->getInterestRate());
    }

    public function testOpenAccountSetsCorrectRateForBoundaryIncome(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 5_000_00])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $accId = $service->openAccount(Uuid::uuid4()->toString());
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(1.02, $account->getInterestRate());
    }

    public function testOpenAccountSetsUnknownRateWhenIncomeIsNull(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => null])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $accId = $service->openAccount(Uuid::uuid4()->toString());
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(0.5, $account->getInterestRate());
    }

    public function testInterestRateRemainsConstantForLowIncomeAccount(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode(['income' => 4_000_00])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(0.93, $account->getInterestRate());

        $service->depositForUser($userId, $accId, 10_00);
        $threeDaysLater = (new DateTimeImmutable())->add(new DateInterval('P3D'));

        $service->calculateInterest($accId, $threeDaysLater);
        $threeDaysLater = (new DateTimeImmutable())->add(new DateInterval('P3D'));

        $service->calculateInterest($accId, $threeDaysLater);

        $accountAfter = $accountRepo->get($accId);
        $this->assertNotNull($accountAfter);
        $this->assertSame(0.93, $accountAfter->getInterestRate());
    }

    public function testInterestRateRemainsConstantForHighIncomeAccount(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode(['income' => 10_000_00])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(1.02, $account->getInterestRate());

        $service->depositForUser($userId, $accId, 50_00);
        $service->calculateInterest($accId);
        $service->calculateInterest($accId);

        $accountAfter = $accountRepo->get($accId);
        $this->assertNotNull($accountAfter);
        $this->assertSame(1.02, $accountAfter->getInterestRate());
    }

    public function testDepositIncreasesBalanceAndRecordsTransaction(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(0, $account->getBalance());

        $service->depositForUser($userId, $accId, 123);

        $updated = $accountRepo->get($accId);
        $this->assertSame(123, $updated->getBalance());

        $statement = $service->getStatement($accId);
        $this->assertCount(1, $statement);
        $this->assertSame('DEPOSIT', $statement[0]['type']);
        $this->assertSame(123, $statement[0]['amount']);
    }

    public function testDepositToNonexistentAccountThrows(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Account not found');
        $service->depositForUser($userId, 'non-existent-id', 100);
    }

    public function testDepositForUserSucceedsForOwner(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);

        $service->depositForUser($userId, $accId, 500);

        $updated = $accountRepo->get($accId);
        $this->assertSame(500, $updated->getBalance());
        $statement = $service->getStatement($accId);
        $this->assertCount(1, $statement);
        $this->assertSame('DEPOSIT', $statement[0]['type']);
        $this->assertSame(500, $statement[0]['amount']);
    }

    public function testDepositForUserFailsForNonOwner(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $ownerId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($ownerId);
        $otherUserId = Uuid::uuid4()->toString();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not authorized for this account');
        $service->depositForUser($otherUserId, $accId, 1000);
    }

    public function testCalculateInterestUsesBalanceForHighRate(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 10_000_00])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $service->depositForUser($userId, $accId, 1_000_00); // £1000.00

        // Expected payout for 3 days at 1.02% p.a. (time-based calculator still defaults to 3 days on first run)
        $balance = 100000; // pennies
        $rateDecimal = 1.02 / 100.0;
        $rawInterest = ($balance * $rateDecimal / 365.0) * 3.0;
        $expectedPayout = (int) floor($rawInterest);

        $threeDaysLater = (new DateTimeImmutable())->add(new DateInterval('P3D'));

        $service->calculateInterest($accId, $threeDaysLater);

        $account = $accountRepo->get($accId);
        $this->assertSame(1_000_00 + $expectedPayout, $account->getBalance());

        $statement = $service->getStatement($accId);
        $this->assertCount(2, $statement); // DEPOSIT + INTEREST
        $this->assertSame('INTEREST', $statement[1]['type']);
        $this->assertSame($expectedPayout, $statement[1]['amount']);
    }

    public function testCalculateInterestUsesBalanceForLowRate(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 4_000_00])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $service->depositForUser($userId, $accId, 1_000_00); // £1000.00

        // Expected payout for 3 days at 0.93% p.a. (time-based calculator still defaults to 3 days on first run)
        $balance = 100000; // pennies
        $rateDecimal = 0.93 / 100.0;
        $rawInterest = ($balance * $rateDecimal / 365.0) * 3.0;
        $expectedPayout = (int) floor($rawInterest);

        $threeDaysLater = (new DateTimeImmutable())->add(new DateInterval('P3D'));

        $service->calculateInterest($accId, $threeDaysLater);

        $account = $accountRepo->get($accId);
        $this->assertSame(1_000_00 + $expectedPayout, $account->getBalance());

        $statement = $service->getStatement($accId);
        $this->assertCount(2, $statement); // DEPOSIT + INTEREST
        $this->assertSame('INTEREST', $statement[1]['type']);
        $this->assertSame($expectedPayout, $statement[1]['amount']);
    }

    public function testOpenAccountSetsUnknownRateWhenStatsApiErrors(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andThrow(new \GuzzleHttp\Exception\RequestException('error', new \GuzzleHttp\Psr7\Request('GET', '/users/x')));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $accId = $service->openAccount(Uuid::uuid4()->toString());
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertSame(0.5, $account->getInterestRate());
    }

    public function testUserCannotOpenMoreThanOneActiveAccount(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $service->openAccount($userId);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User already has an active account');
        $service->openAccount($userId);
    }

    public function testSkipSubPennyAndCarryover(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));
        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $service->depositForUser($userId, $accId, 100_00); // £100.00

        // Calculate interest for one 3-day period: accrues <1p, so no INTEREST payout yet.
        // Rate 0.93% -> daily interest in pennies: 10000 * 0.0093 / 365 ≈ 0.2548p per day
        // For 3 days ≈ 0.7644p -> first cycle pays 0p, carry ~0.7644
        $threeDaysLater = (new DateTimeImmutable())->add(new DateInterval('P3D'));
        $service->calculateInterest($accId, $threeDaysLater);
        $statement = $service->getStatement($accId);
        // Only the initial deposit should be present; no INTEREST payout yet
        $this->assertCount(1, $statement);
        $this->assertSame('DEPOSIT', $statement[0]['type']);

        // After two 3-day periods, ≈ 1.5288p -> pays 1p
        $sixDaysLater = $threeDaysLater->add(new DateInterval('P3D'));
        $service->calculateInterest($accId, $sixDaysLater);
        $statement = $service->getStatement($accId);
        $this->assertCount(2, $statement); // one DEPOSIT and one INTEREST
        $this->assertSame('DEPOSIT', $statement[0]['type']);
        $this->assertSame('INTEREST', $statement[1]['type']);
        $this->assertSame(100_00, $statement[0]['amount']);
        $this->assertSame(1, $statement[1]['amount']);
    }

    public function testCalculateInterestSetsLastInterestRunAt(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);

        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $this->assertNull($account->getLastInterestRunAt());

        $service->calculateInterest($accId);

        $updated = $accountRepo->get($accId);
        $this->assertNotNull($updated);
        $this->assertInstanceOf(DateTimeImmutable::class, $updated->getLastInterestRunAt());
    }

    public function testCalculateInterestUpdatesLastInterestRunAtOnSubsequentRuns(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 100000])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);

        $service->calculateInterest($accId);
        $afterFirst = $accountRepo->get($accId);
        $this->assertNotNull($afterFirst);
        $firstRunAt = $afterFirst->getLastInterestRunAt();
        $this->assertInstanceOf(DateTimeImmutable::class, $firstRunAt);

        // Run again and ensure the timestamp is updated
        $service->calculateInterest($accId);
        $afterSecond = $accountRepo->get($accId);
        $this->assertNotNull($afterSecond);
        $secondRunAt = $afterSecond->getLastInterestRunAt();
        $this->assertInstanceOf(DateTimeImmutable::class, $secondRunAt);

        // It is sufficient for this test to assert that the value changed
        $this->assertNotEquals($firstRunAt, $secondRunAt);
    }

    public function testJitterLateRunCalculatesInterestForAllElapsedDays(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 10_000_00])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $service->depositForUser($userId, $accId, 1_000_00); // £1000.00

        // Simulate a scheduler that last ran 4 days ago
        $account = $accountRepo->get($accId);
        $this->assertNotNull($account);
        $fourDaysAgo = (new DateTimeImmutable())->sub(new DateInterval('P4D'));
        $account->setLastInterestRunAt($fourDaysAgo);
        $accountRepo->save($account);

        // Expected payout for 4 days at 1.02% p.a.
        $balance = 100000; // pennies
        $rateDecimal = 1.02 / 100.0;
        $rawInterest = ($balance * $rateDecimal / 365.0) * 4.0;
        $expectedPayout = (int) floor($rawInterest);

        $service->calculateInterest($accId);

        $updated = $accountRepo->get($accId);
        $this->assertNotNull($updated);
        $this->assertSame(1_000_00 + $expectedPayout, $updated->getBalance());
    }

    public function testJitterDuplicateSameDayRunDoesNotDoublePayInterest(): void
    {
        $mockHttp = Mockery::mock(ClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn(new Response(200, [], json_encode(['income' => 10_000_00])));

        $service = $this->createServiceWithMock($mockHttp, $accountRepo, $txnRepo);

        $userId = Uuid::uuid4()->toString();
        $accId = $service->openAccount($userId);
        $service->depositForUser($userId, $accId, 1_000_00); // £1000.00

        // First run: pays for an initial 3-day period
        $threeDaysLater = (new DateTimeImmutable())->add(new DateInterval('P3D'));
        $service->calculateInterest($accId, $threeDaysLater);
        $afterFirst = $accountRepo->get($accId);
        $this->assertNotNull($afterFirst);
        $balanceAfterFirst = $afterFirst->getBalance();

        // Second run, same "now": should see 0 elapsed days
        $service->calculateInterest($accId, $threeDaysLater);
        $afterSecond = $accountRepo->get($accId);
        $this->assertNotNull($afterSecond);

        // Balance should be unchanged by the second run
        $this->assertSame($balanceAfterFirst, $afterSecond->getBalance());

        // And there should still be only one INTEREST transaction
        $statement = $service->getStatement($accId);
        $this->assertCount(2, $statement); // one DEPOSIT and one INTEREST
        $this->assertSame('DEPOSIT', $statement[0]['type']);
        $this->assertSame('INTEREST', $statement[1]['type']);
    }
}
