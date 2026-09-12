<?php

declare(strict_types=1);

namespace RuFaker\Tests\Faker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\Gender;
use RuFaker\Enum\LegalForm;
use RuFaker\Faker\Values;
use RuFaker\Requisite\Bik;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\RuFaker;

#[CoversClass(Values::class)]
final class ValuesTest extends TestCase
{
    #[Test]
    public function it_generates_requisites_that_pass_their_own_checks(): void
    {
        $values = new Values();

        $this->assertTrue(
            Inn::isValid($values->inn()),
        );

        $this->assertTrue(
            Ogrn::isValid($values->ogrn()),
        );

        $this->assertTrue(
            Kpp::isValid($values->kpp()),
        );

        $this->assertTrue(
            Bik::isValid($values->bik()),
        );
    }

    #[Test]
    public function it_passes_the_arguments_on(): void
    {
        $values = new Values();

        $this->assertSame(
            10,
            strlen($values->inn(LegalForm::Ooo)),
        );

        $this->assertStringStartsWith(
            '77',
            $values->kpp(Region::from('77')),
        );

        $this->assertStringStartsWith(
            '3',
            $values->ogrn(LegalForm::Ip),
        );

        $this->assertSame(
            'женский',
            $values->person(Gender::Female)['gender'],
        );

        $this->assertSame(
            '047132106',
            $values->bankAccount(Bik::from('047132106'))['bik'],
        );

        $this->assertNull(
            $values->organization(LegalForm::Ip)['kpp'],
        );
    }

    #[Test]
    public function it_hands_over_the_same_keys_the_package_does(): void
    {
        $values = new Values();

        $this->assertSame(
            [
                'form',
                'short_name',
                'full_name',
                'region',
                'inn',
                'ogrn',
                'kpp',
                'person',
            ],
            array_keys($values->organization()),
        );

        $this->assertSame(
            [
                'gender',
                'last_name',
                'first_name',
                'patronymic',
            ],
            array_keys($values->person()),
        );

        $this->assertSame(
            [
                'bank_short_name',
                'bank_full_name',
                'bik',
                'correspondent_account',
                'settlement_account',
            ],
            array_keys($values->bankAccount()),
        );
    }

    #[Test]
    public function it_repeats_the_package_when_given_one(): void
    {
        $this->assertSame(
            RuFaker::seeded(1234)->inn()->value,
            (new Values(RuFaker::seeded(1234)))->inn(),
        );
    }
}
