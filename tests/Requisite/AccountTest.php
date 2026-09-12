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

    /** First three digits of a correspondent account, the group held at the Bank of Russia. */
    private const int CLASSIFYING_DIGITS = 3;

    #[Test]
    #[DataProvider('realAccounts')]
    public function it_accepts_a_real_correspondent_account(string $account, string $bik): void
    {
        $this->assertTrue(
            Account::isValid($account, Bik::from($bik)),
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

        $this->assertBrokenByEveryChange($account, $bik, 0);
    }

    #[Test]
    public function it_rejects_every_single_digit_change_below_the_account_group(): void
    {
        // The first three digits pick the keying rule, so changing them moves the account group.
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
