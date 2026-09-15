<?php

declare(strict_types=1);

namespace RuFaker\Tests\Requisite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;

#[CoversClass(Account::class)]
final class AccountTest extends TestCase
{
    /** Sberbank, whose Bank of Russia division is 25. */
    private const string MOSCOW_BIK = '044525225';

    /** Correspondent account Sberbank holds at that division. */
    private const string MOSCOW_CORRESPONDENT = '30101810400000000225';

    /** North-West branch of Sberbank, whose division is 30. */
    private const string NORTHWEST_BIK = '044030653';

    /** Correspondent account that branch holds at its own division. */
    private const string NORTHWEST_CORRESPONDENT = '30101810500000000653';

    /** Another bank of division 25: only the participant number tells it from Sberbank. */
    private const string SAME_DIVISION_BIK = '044525999';

    /** Balance account, the five digits that pick which bank code the key is computed over. */
    private const int CLASSIFYING_DIGITS = 5;

    #[Test]
    #[DataProvider('realAccounts')]
    public function it_accepts_a_real_correspondent_account(string $account, string $bik): void
    {
        $this->assertTrue(
            Account::isValid($account, Bik::from($bik)),
        );
    }

    #[Test]
    #[DataProvider('realCustomerAccounts')]
    public function it_accepts_a_real_customer_account(string $account, string $bik): void
    {
        $this->assertTrue(
            Account::isValid($account, Bik::from($bik)),
        );
    }

    #[Test]
    #[DataProvider('realTreasuryAccounts')]
    public function it_accepts_a_real_account_held_at_the_treasury(string $account, string $bik): void
    {
        $this->assertTrue(
            Account::isValid($account, Bik::from($bik)),
        );
    }

    #[Test]
    #[DataProvider('malformedAccounts')]
    public function it_rejects_a_value_that_is_not_twenty_digits(string $value): void
    {
        $this->assertFalse(
            Account::isValid($value, Bik::from(self::MOSCOW_BIK)),
        );
    }

    #[Test]
    public function it_rejects_an_account_of_a_bank_with_another_division(): void
    {
        $this->assertFalse(
            Account::isValid(self::MOSCOW_CORRESPONDENT, Bik::from(self::NORTHWEST_BIK)),
        );
    }

    #[Test]
    public function it_accepts_a_correspondent_account_of_a_bank_sharing_the_division(): void
    {
        // A correspondent account is keyed by the division, so every bank of it gets the same key.
        $this->assertTrue(
            Account::isValid(self::MOSCOW_CORRESPONDENT, Bik::from(self::SAME_DIVISION_BIK)),
        );
    }

    #[Test]
    public function it_rejects_every_single_digit_change_in_a_customer_account(): void
    {
        $bik = Bik::from(self::MOSCOW_BIK);
        $account = Account::fromDraft('40702810000000000001', $bik)->value;

        $this->assertBrokenByEveryChange($account, $bik, self::CLASSIFYING_DIGITS);
    }

    #[Test]
    public function it_rejects_every_single_digit_change_below_the_account_group(): void
    {
        // Changing the balance account moves the number to another group, keyed by another rule.
        $this->assertBrokenByEveryChange(
            self::MOSCOW_CORRESPONDENT,
            Bik::from(self::MOSCOW_BIK),
            self::CLASSIFYING_DIGITS,
        );
    }

    #[Test]
    public function it_computes_the_control_key_of_a_correspondent_account(): void
    {
        $this->assertSame(
            self::MOSCOW_CORRESPONDENT,
            Account::fromDraft('30101810000000000225', Bik::from(self::MOSCOW_BIK))->value,
        );

        $this->assertSame(
            self::NORTHWEST_CORRESPONDENT,
            Account::fromDraft('30101810000000000653', Bik::from(self::NORTHWEST_BIK))->value,
        );
    }

    #[Test]
    public function it_tells_a_settlement_account_from_a_correspondent_one(): void
    {
        $bik = Bik::from(self::MOSCOW_BIK);
        $account = Account::fromDraft('40702810000000000001', $bik);

        $this->assertTrue(
            Account::isValid($account->value, $bik),
        );

        $this->assertFalse(
            $account->isCorrespondent(),
        );

        $this->assertTrue(
            $account->isSettlement(),
        );

        $correspondent = Account::from(self::MOSCOW_CORRESPONDENT, $bik);

        $this->assertTrue(
            $correspondent->isCorrespondent(),
        );

        $this->assertFalse(
            $correspondent->isSettlement(),
        );
    }

    #[Test]
    public function it_rejects_a_draft_of_a_wrong_length(): void
    {
        $this->expectException(InvalidRequisite::class);

        Account::fromDraft('4070281000000000', Bik::from(self::MOSCOW_BIK));
    }

    #[Test]
    public function it_throws_on_an_account_of_another_division(): void
    {
        $this->expectException(InvalidRequisite::class);

        Account::from(self::MOSCOW_CORRESPONDENT, Bik::from(self::NORTHWEST_BIK));
    }

    #[Test]
    public function it_returns_null_on_an_account_of_another_division(): void
    {
        $this->assertNull(
            Account::tryFrom(self::MOSCOW_CORRESPONDENT, Bik::from(self::NORTHWEST_BIK)),
        );
    }

    /**
     * Correspondent account of every real bank, paired with its BIK.
     *
     * @return array<string, array{string, string}>
     */
    public static function realAccounts(): array
    {
        return [
            'Sberbank, Moscow' => [
                self::MOSCOW_CORRESPONDENT,
                self::MOSCOW_BIK,
            ],
            'Sberbank, North-West' => [
                self::NORTHWEST_CORRESPONDENT,
                self::NORTHWEST_BIK,
            ],
            'Alfa-Bank, Moscow' => [
                '30101810200000000593',
                '044525593',
            ],
        ];
    }

    /**
     * Values that break the account format.
     *
     * @return array<string, array{string}>
     */
    public static function malformedAccounts(): array
    {
        return [
            'empty' => [''],
            'nineteen digits' => ['3010181040000000022'],
            'twenty-one digits' => ['301018104000000002251'],
            'letters' => ['3010181040000000022a'],
        ];
    }

    /**
     * Accounts organizations hold at their banks, published by the organizations themselves.
     *
     * @return array<string, array{string, string}>
     */
    public static function realCustomerAccounts(): array
    {
        return [
            'a company at Sberbank' => [
                '40702810738060051258',
                self::MOSCOW_BIK,
            ],
            'the same company at Alfa-Bank' => [
                '40702810101300049173',
                '044525593',
            ],
            'a foundation at Sberbank' => [
                '40703810438040104602',
                self::MOSCOW_BIK,
            ],
        ];
    }

    /**
     * Accounts held at a Bank of Russia division, taken from the tax payment details and the BIK directory.
     *
     * @return array<string, array{string, string}>
     */
    public static function realTreasuryAccounts(): array
    {
        return [
            'single treasury account of the Tula region' => [
                '40102810445370000059',
                '017003983',
            ],
            'treasury account the tax payments go to' => [
                '03100643000000018500',
                '017003983',
            ],
            'a university served by the federal treasury' => [
                '40501810150042006001',
                '048142001',
            ],
        ];
    }

    /**
     * Asserts that changing any digit from the given position on breaks the control key.
     *
     * @param string $account
     * @param Bik $bik
     * @param int $from
     * @return void
     */
    private function assertBrokenByEveryChange(string $account, Bik $bik, int $from): void
    {
        foreach (str_split($account) as $position => $original) {
            if ($position < $from) {
                continue;
            }

            foreach (str_split('0123456789') as $digit) {
                if ($digit === $original) {
                    continue;
                }

                $this->assertFalse(
                    Account::isValid(substr_replace($account, $digit, $position, 1), $bik),
                    "A change at position $position must break the account number.",
                );
            }
        }
    }
}
