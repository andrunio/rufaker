<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Enum\LegalForm;
use RuFaker\Internal\Generator\OrganizationGenerator;
use RuFaker\Internal\TitleBook;
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

    /** Latest registration year the generator may encode, mirroring its own constant. */
    private const int LAST_YEAR = 26;

    #[Test]
    public function it_generates_requisites_that_pass_their_own_validation(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate();

            $this->assertTrue(
                Inn::isValid($organization->inn),
            );

            $this->assertTrue(
                Ogrn::isValid($organization->ogrn),
            );

            if ($organization->kpp !== null) {
                $this->assertTrue(
                    Kpp::isValid($organization->kpp),
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
            $region = $organization->region;

            $this->assertSame(
                $region,
                $organization->inn()->region()?->value,
            );

            $this->assertSame(
                $region,
                $organization->ogrn()->region()?->value,
            );

            if ($organization->kpp !== null) {
                $this->assertSame(
                    $region,
                    $organization->kpp()?->region()?->value,
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
                substr($organization->ogrn, 5, 2),
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
            strlen($organization->inn),
        );

        $this->assertSame(
            $form->registryNumberDigits(),
            strlen($organization->ogrn),
        );

        $this->assertSame(
            $form->hasKpp(),
            $organization->kpp !== null,
        );
    }

    #[Test]
    #[DataProvider('registryOpenings')]
    public function it_dates_a_registry_number_within_the_life_of_its_registry(LegalForm $form, int $firstYear): void
    {
        $generator = $this->generator();
        $years = [];

        foreach (range(1, self::RUNS) as $ignored) {
            // The year sits right behind the leading digit that tells the two registries apart.
            $years[] = (int)substr($generator->generate($form)->ogrn, 1, 2);
        }

        $this->assertSame(
            $firstYear,
            min($years),
        );

        $this->assertSame(
            self::LAST_YEAR,
            max($years),
        );
    }

    #[Test]
    public function it_honours_a_requested_region(): void
    {
        $organization = $this->generator()->generate(LegalForm::Ooo, Region::from('77'));

        $this->assertSame(
            '77',
            $organization->region,
        );

        $this->assertStringStartsWith(
            '77',
            $organization->inn,
        );
    }

    #[Test]
    public function it_names_a_legal_entity_out_of_the_title_book(): void
    {
        $generator = $this->generator();

        $expected = array_map(
            static fn(string $title): string => 'ООО "' . $title . '"',
            TitleBook::titles(),
        );

        foreach (range(1, self::RUNS) as $ignored) {
            $this->assertContains(
                $generator->generate(LegalForm::Ooo)->shortName,
                $expected,
            );
        }
    }

    #[Test]
    public function it_cuts_a_sole_proprietor_to_initials_only_when_asked(): void
    {
        $generator = $this->generator();

        $plain = $generator->generate(LegalForm::Ip);

        $this->assertSame(
            'ИП ' . $plain->person()?->fullName,
            $plain->shortName,
        );

        $short = $generator->generate(LegalForm::Ip, initials: true);

        $this->assertMatchesRegularExpression(
            '/^ИП [А-ЯЁ][а-яё]+ [А-ЯЁ]\.[А-ЯЁ]\.$/u',
            $short->shortName,
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
     * Every form against the year its registry opened, taken from the law rather than from the code.
     *
     * @return array<string, array{LegalForm, int}>
     */
    public static function registryOpenings(): array
    {
        return [
            'limited liability company' => [LegalForm::Ooo, 2],
            'joint-stock company' => [LegalForm::Ao, 2],
            'public joint-stock company' => [LegalForm::Pao, 2],
            'sole proprietor' => [LegalForm::Ip, 4],
        ];
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
