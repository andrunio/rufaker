<?php

declare(strict_types=1);

namespace RuFaker\Tests\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Enum\LegalForm;
use RuFaker\Generator\OrganizationGenerator;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\Result\Organization;

#[CoversClass(OrganizationGenerator::class)]
#[CoversClass(Organization::class)]
final class OrganizationGeneratorTest extends TestCase
{
    /** How many sets each property is checked over. */
    private const int RUNS = 300;

    #[Test]
    public function it_generates_requisites_that_pass_their_own_validation(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate();

            $this->assertTrue(
                Inn::isValid($organization->inn->value),
            );

            $this->assertTrue(
                Ogrn::isValid($organization->ogrn->value),
            );

            if ($organization->kpp instanceof Kpp) {
                $this->assertTrue(
                    Kpp::isValid($organization->kpp->value),
                );
            }
        }
    }

    #[Test]
    public function it_keeps_every_requisite_in_one_region(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate();
            $region = $organization->region->value;

            $this->assertSame(
                $region,
                $organization->inn->region()?->value,
            );

            $this->assertSame(
                $region,
                $organization->ogrn->region()?->value,
            );

            if ($organization->kpp instanceof Kpp) {
                $this->assertSame(
                    $region,
                    $organization->kpp->region()?->value,
                );
            }
        }
    }

    #[Test]
    public function it_shares_one_tax_office_between_the_registry_number_and_the_kpp(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate(LegalForm::Ooo);

            $this->assertSame(
                substr($organization->ogrn->value, 5, 2),
                substr((string)$organization->kpp, 2, 2),
            );
        }
    }

    #[Test]
    #[DataProvider('forms')]
    public function it_follows_the_structure_of_the_legal_form(LegalForm $form): void
    {
        $organization = $this->generator()->generate($form);

        $this->assertSame(
            $form->innDigits(),
            strlen($organization->inn->value),
        );

        $this->assertSame(
            $form->registryNumberDigits(),
            strlen($organization->ogrn->value),
        );

        $this->assertSame(
            $form->hasKpp(),
            $organization->kpp instanceof Kpp,
        );
    }

    #[Test]
    public function it_honours_a_requested_region(): void
    {
        $organization = $this->generator()->generate(LegalForm::Ooo, Region::from('77'));

        $this->assertSame(
            '77',
            $organization->region->value,
        );

        $this->assertStringStartsWith(
            '77',
            $organization->inn->value,
        );
    }

    #[Test]
    public function it_builds_standalone_requisites(): void
    {
        $generator = $this->generator();

        $this->assertTrue(
            Inn::isValid($generator->inn()->value),
        );

        $this->assertTrue(
            Ogrn::isValid($generator->registryNumber()->value),
        );

        $this->assertTrue(
            Kpp::isValid($generator->kpp()->value),
        );
    }

    /**
     * Every legal form, keyed by its own value so a failure names the form.
     *
     * @return array<string, array{LegalForm}>
     */
    public static function forms(): array
    {
        return array_combine(
            array_map(
                static fn(LegalForm $form): string => $form->value,
                LegalForm::cases(),
            ),
            array_map(
                static fn(LegalForm $form): array => [$form],
                LegalForm::cases(),
            ),
        );
    }

    /**
     * Builds a generator whose randomizer repeats itself for the same seed.
     *
     * @return OrganizationGenerator
     */
    private function generator(): OrganizationGenerator
    {
        return new OrganizationGenerator(
            new Randomizer(
                new Mt19937(1234),
            ),
        );
    }
}
