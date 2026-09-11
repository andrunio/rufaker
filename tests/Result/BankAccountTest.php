<?php

declare(strict_types=1);

namespace RuFaker\Tests\Result;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;
use RuFaker\Result\BankAccount;

#[CoversClass(BankAccount::class)]
final class BankAccountTest extends TestCase
{
    /** Sberbank, whose Bank of Russia division is 25. */
    private const string MOSCOW_BIK = '044525225';

    /** Correspondent account Sberbank holds at that division. */
    private const string MOSCOW_CORRESPONDENT = '30101810400000000225';

    /** Settlement account of a customer of that bank, completed by the package itself. */
    private const string MOSCOW_SETTLEMENT = '40702810200000000001';

    /** North-West branch of Sberbank, whose division is 30. */
    private const string NORTHWEST_BIK = '044030653';

    /** Correspondent account that branch holds at its own division. */
    private const string NORTHWEST_CORRESPONDENT = '30101810500000000653';

    /** Settlement account of a customer of that branch, completed by the package itself. */
    private const string NORTHWEST_SETTLEMENT = '40702810700000000001';

    #[Test]
    public function it_assembles_details_of_one_bank(): void
    {
        $details = $this->details();

        $this->assertSame(
            self::MOSCOW_BIK,
            $details->bik,
        );

        $this->assertTrue(
            $details->correspondent()->isCorrespondent(),
        );

        $this->assertFalse(
            $details->settlement()->isCorrespondent(),
        );
    }

    #[Test]
    public function it_rejects_a_correspondent_account_of_another_bank(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Correspondent account ' . self::NORTHWEST_CORRESPONDENT . ' is not keyed to BIK ' . self::MOSCOW_BIK . '.');

        new BankAccount(
            Bik::from(self::MOSCOW_BIK),
            $this->account(self::NORTHWEST_CORRESPONDENT, self::NORTHWEST_BIK),
            $this->account(self::MOSCOW_SETTLEMENT, self::MOSCOW_BIK),
        );
    }

    #[Test]
    public function it_rejects_a_settlement_account_of_another_bank(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Settlement account ' . self::NORTHWEST_SETTLEMENT . ' is not keyed to BIK ' . self::MOSCOW_BIK . '.');

        new BankAccount(
            Bik::from(self::MOSCOW_BIK),
            $this->account(self::MOSCOW_CORRESPONDENT, self::MOSCOW_BIK),
            $this->account(self::NORTHWEST_SETTLEMENT, self::NORTHWEST_BIK),
        );
    }

    #[Test]
    public function it_rejects_a_settlement_account_in_place_of_the_correspondent_one(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Account ' . self::MOSCOW_SETTLEMENT . ' is not a correspondent one.');

        new BankAccount(
            Bik::from(self::MOSCOW_BIK),
            $this->account(self::MOSCOW_SETTLEMENT, self::MOSCOW_BIK),
            $this->account(self::MOSCOW_SETTLEMENT, self::MOSCOW_BIK),
        );
    }

    #[Test]
    public function it_rejects_a_correspondent_account_in_place_of_the_settlement_one(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Account ' . self::MOSCOW_CORRESPONDENT . ' is a correspondent one.');

        new BankAccount(
            Bik::from(self::MOSCOW_BIK),
            $this->account(self::MOSCOW_CORRESPONDENT, self::MOSCOW_BIK),
            $this->account(self::MOSCOW_CORRESPONDENT, self::MOSCOW_BIK),
        );
    }

    #[Test]
    public function it_exports_the_details_as_strings(): void
    {
        $this->assertSame(
            [
                'bik' => self::MOSCOW_BIK,
                'correspondent_account' => self::MOSCOW_CORRESPONDENT,
                'settlement_account' => self::MOSCOW_SETTLEMENT,
            ],
            $this->details()->toArray(),
        );
    }

    #[Test]
    public function it_encodes_itself_to_json(): void
    {
        $details = $this->details();

        $this->assertSame(
            $details->toArray(),
            $details->jsonSerialize(),
        );

        $this->assertSame(
            '{"bik":"044525225","correspondent_account":"30101810400000000225","settlement_account":"40702810200000000001"}',
            json_encode($details),
        );
    }

    #[Test]
    public function it_gives_a_typed_form_of_every_field(): void
    {
        $details = $this->details();

        $this->assertSame(
            $details->bik,
            $details->bik()->value,
        );

        $this->assertSame(
            $details->correspondent,
            $details->correspondent()->value,
        );

        $this->assertSame(
            $details->settlement,
            $details->settlement()->value,
        );
    }

    /**
     * Builds payment details whose accounts are both keyed to their own bank.
     *
     * @return BankAccount
     * @throws InvalidRequisite
     */
    private function details(): BankAccount
    {
        return new BankAccount(
            Bik::from(self::MOSCOW_BIK),
            $this->account(self::MOSCOW_CORRESPONDENT, self::MOSCOW_BIK),
            $this->account(self::MOSCOW_SETTLEMENT, self::MOSCOW_BIK),
        );
    }

    /**
     * Wraps an account number against the bank it was opened at.
     *
     * @param string $value
     * @param string $bik
     * @return Account
     * @throws InvalidRequisite
     */
    private function account(string $value, string $bik): Account
    {
        return Account::from($value, Bik::from($bik));
    }
}
