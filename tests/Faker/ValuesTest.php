<?php

declare(strict_types=1);

namespace RuFaker\Tests\Faker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
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
    public function it_hands_out_nothing_but_strings_and_arrays(): void
    {
        $methods = (new ReflectionClass(Values::class))
            ->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->isConstructor()) {
                continue;
            }

            $type = $method->getReturnType();

            // A value goes from here straight into a model attribute and then into PDO.
            $this->assertInstanceOf(ReflectionNamedType::class, $type);

            $this->assertContains(
                $type->getName(),
                ['string', 'array'],
                sprintf('Values::%s() must answer a string or an array, not an object.', $method->getName()),
            );
        }
    }

    #[Test]
    public function it_offers_every_generator_of_the_package(): void
    {
        $core = (new ReflectionClass(RuFaker::class))->getMethods(ReflectionMethod::IS_PUBLIC);
        $provided = (new ReflectionClass(Values::class));

        foreach ($core as $method) {
            // A static method builds the package itself; Faker seeds the provider on its own.
            if ($method->isConstructor() || $method->isStatic()) {
                continue;
            }

            $name = $method->getName();

            $this->assertTrue(
                $provided->hasMethod($name),
                "RuFaker::$name() has no twin in Values: the provider is the main channel, "
                . 'and a generator missing from it reaches no Faker user.',
            );

            $this->assertSame(
                $this->signature($method),
                $this->signature($provided->getMethod($name)),
                "Values::$name() takes arguments other than RuFaker::$name() does.",
            );
        }
    }

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

    /**
     * Reads the parameters of a method as a comparable list.
     *
     * @param ReflectionMethod $method
     * @return list<string>
     */
    private function signature(ReflectionMethod $method): array
    {
        return array_map(
            static fn(ReflectionParameter $parameter): string => sprintf(
                '%s $%s%s',
                (string)$parameter->getType(),
                $parameter->getName(),
                $parameter->isOptional() ? ' = …' : '',
            ),
            $method->getParameters(),
        );
    }
}
