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
    public function it_throws_on_an_invalid_code(): void
    {
        $this->expectException(InvalidRequisite::class);

        Region::from('00');
    }

    #[Test]
    public function it_draws_only_from_its_own_pool(): void
    {
        $randomizer = new Randomizer(new Mt19937(1234));

        foreach (range(1, 200) as $ignored) {
            $this->assertContains(
                Region::random($randomizer)->value,
                Region::pool(),
            );
        }
    }

    #[Test]
    public function its_pool_holds_no_duplicates(): void
    {
        $pool = Region::pool();

        $this->assertSame(
            $pool,
            array_values(array_unique($pool)),
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
