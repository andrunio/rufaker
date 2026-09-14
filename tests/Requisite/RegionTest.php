<?php

declare(strict_types=1);

namespace RuFaker\Tests\Requisite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Region;

#[CoversClass(Region::class)]
final class RegionTest extends TestCase
{
    /** Codes the generator is allowed to draw, listed apart from the pool it draws them from. */
    private const array GENERATED_CODES = [
        '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
        '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
        '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
        '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
        '41', '42', '43', '44', '45', '46', '47', '48', '49', '50',
        '51', '52', '53', '54', '55', '56', '57', '58', '59', '60',
        '61', '62', '63', '64', '65', '66', '67', '68', '69', '70',
        '71', '72', '73', '74', '75', '76', '77', '78', '79', '83',
        '86', '87', '89',
    ];

    /** Draws enough for every code of the pool to come up: this seed needs 351 of them. */
    private const int DRAWS = 1000;

    #[Test]
    #[DataProvider('acceptedCodes')]
    public function it_accepts_any_code_of_the_range(string $code): void
    {
        $this->assertTrue(
            Region::isValid($code),
        );
    }

    #[Test]
    #[DataProvider('rejectedCodes')]
    public function it_rejects_a_code_outside_the_range(string $code): void
    {
        $this->assertFalse(
            Region::isValid($code),
        );
    }

    #[Test]
    public function it_wraps_a_code_of_the_range(): void
    {
        $this->assertSame(
            '77',
            Region::from('77')->value,
        );

        $this->assertSame(
            '78',
            Region::tryFrom('78')?->value,
        );
    }

    #[Test]
    public function it_throws_on_an_invalid_code(): void
    {
        $this->expectException(InvalidRequisite::class);

        Region::from('00');
    }

    #[Test]
    public function it_draws_every_code_of_its_pool_and_no_other(): void
    {
        $randomizer = new Randomizer(new Mt19937(1234));

        $drawn = [];

        foreach (range(1, self::DRAWS) as $ignored) {
            $drawn[] = Region::random($randomizer)->value;
        }

        // Keys would not do: PHP turns '77' into an integer key and leaves '01' a string.
        $codes = array_values(array_unique($drawn));
        sort($codes, SORT_STRING);

        $this->assertSame(
            self::GENERATED_CODES,
            $codes,
        );
    }

    /**
     * Codes a region is allowed to carry.
     *
     * @return array<string, array{string}>
     */
    public static function acceptedCodes(): array
    {
        return [
            'Adygea' => ['01'],
            'Moscow' => ['77'],
            'Saint Petersburg' => ['78'],
            'outside the generation pool' => ['99'],
        ];
    }

    /**
     * Codes outside the range of a federal subject.
     *
     * @return array<string, array{string}>
     */
    public static function rejectedCodes(): array
    {
        return [
            'empty' => [''],
            'zero' => ['00'],
            'single digit' => ['7'],
            'three digits' => ['077'],
            'letters' => ['ab'],
        ];
    }
}
