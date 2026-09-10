<?php

declare(strict_types=1);

namespace RuFaker\Tests\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Enum\LegalForm;
use RuFaker\Generator\BankAccountGenerator;
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;
use RuFaker\Result\BankAccount;

#[CoversClass(BankAccountGenerator::class)]
#[CoversClass(BankAccount::class)]
final class BankAccountGeneratorTest extends TestCase
{
    /** How many sets each property is checked over. */
    private const int RUNS = 300;

    #[Test]
    public function it_keys_both_accounts_to_their_own_bank(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $details = $generator->generate();

            $this->assertTrue(
                Account::isValid($details->correspondent->value, $details->bik),
            );

            $this->assertTrue(
                Account::isValid($details->settlement->value, $details->bik),
            );
        }
    }

    #[Test]
    public function it_ends_the_correspondent_account_with_the_participant_number(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $details = $generator->generate();

            $this->assertSame(
                $details->bik->participant(),
                substr($details->correspondent->value, -3),
            );
        }
    }

    #[Test]
    public function it_opens_a_sole_proprietor_account_on_its_own_balance_account(): void
    {
        $details = $this->generator()->generate(null, LegalForm::Ip);

        $this->assertStringStartsWith(
            '40802',
            $details->settlement->value,
        );

        $this->assertStringStartsWith(
            '40702',
            $this->generator()->generate(null, LegalForm::Ooo)->settlement->value,
        );
    }

    #[Test]
    public function it_honours_a_requested_bank(): void
    {
        $details = $this->generator()->generate(Bik::from('044525225'));

        $this->assertSame(
            '044525225',
            $details->bik->value,
        );

        $this->assertSame(
            '30101810400000000225',
            $details->correspondent->value,
        );
    }

    #[Test]
    public function it_generates_a_well_formed_bik(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $this->assertTrue(
                Bik::isValid($generator->bik()->value),
            );
        }
    }

    /**
     * Builds a generator whose randomizer repeats itself for the same seed.
     *
     * @return BankAccountGenerator
     */
    private function generator(): BankAccountGenerator
    {
        return new BankAccountGenerator(
            new Randomizer(
                new Mt19937(1234),
            ),
        );
    }
}
