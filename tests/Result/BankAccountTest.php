<?php

declare(strict_types=1);

namespace RuFaker\Tests\Result;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\LegalForm;
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

    /** Proper name of the bank the tests build, one of the words the package draws from. */
    private const string TITLE = 'Ромашка';

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
    public function it_names_the_bank_in_both_forms(): void
    {
        $details = $this->details();

        $this->assertSame(
            'ПАО "Ромашка Банк"',
            $details->shortName,
        );

        $this->assertSame(
            'Публичное акционерное общество "Ромашка Банк"',
            $details->fullName,
        );
    }

    #[Test]
    public function it_rejects_a_sole_proprietor_as_a_bank(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('A bank cannot be a sole proprietor.');

        new BankAccount(
            Bik::from(self::MOSCOW_BIK),
            $this->account(self::MOSCOW_CORRESPONDENT, self::MOSCOW_BIK),
            $this->account(self::MOSCOW_SETTLEMENT, self::MOSCOW_BIK),
            LegalForm::Ip,
            self::TITLE,
        );
    }

    #[Test]
    public function it_rejects_an_empty_title(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('A bank must have a title.');

        new BankAccount(
            Bik::from(self::MOSCOW_BIK),
            $this->account(self::MOSCOW_CORRESPONDENT, self::MOSCOW_BIK),
            $this->account(self::MOSCOW_SETTLEMENT, self::MOSCOW_BIK),
            LegalForm::Pao,
            '   ',
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
            LegalForm::Pao,
            self::TITLE,
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
            LegalForm::Pao,
            self::TITLE,
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
            LegalForm::Pao,
            self::TITLE,
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
            LegalForm::Pao,
            self::TITLE,
        );
    }

    #[Test]
    public function it_exports_the_details_as_strings(): void
    {
        $this->assertSame(
            [
                'bank_short_name' => 'ПАО "Ромашка Банк"',
                'bank_full_name' => 'Публичное акционерное общество "Ромашка Банк"',
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
            '{"bank_short_name":"ПАО \"Ромашка Банк\"",'
            . '"bank_full_name":"Публичное акционерное общество \"Ромашка Банк\"",'
            . '"bik":"044525225","correspondent_account":"30101810400000000225",'
            . '"settlement_account":"40702810200000000001"}',
            json_encode($details, JSON_UNESCAPED_UNICODE),
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
            LegalForm::Pao,
            self::TITLE,
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
