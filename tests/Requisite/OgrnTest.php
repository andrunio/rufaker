<?php

declare(strict_types=1);

namespace RuFaker\Tests\Requisite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Ogrn;

#[CoversClass(Ogrn::class)]
final class OgrnTest extends TestCase
{
    /** Registry number of a sole proprietor, completed by the package: no value from real life is at hand yet. */
    private const string SOLE_PROPRIETOR = '304500100000017';

    /** Registry number of a record made in 2015, completed by the package: real numbers at hand all date to 2002. */
    private const string LATER_RECORD = '1157746123457';

    /** OGRN opening with 5, the rarer of the two prefixes of a legal entity: register of SMEs, 10.09.2026. */
    private const string FIVE_PREFIXED_ENTITY = '5067746006620';

    /** Number of a record in the register of legal entities, built from the OGRN of Sberbank by its prefix. */
    private const string LEGAL_ENTITY_RECORD = '2027700132194';

    /** Number of a record in the register of sole proprietors, built from an OGRNIP of the register of SMEs. */
    private const string SOLE_PROPRIETOR_RECORD = '423265100024467';

    #[Test]
    #[DataProvider('realNumbers')]
    public function it_accepts_a_real_registry_number(string $value): void
    {
        $this->assertTrue(
            Ogrn::isValid($value),
        );
    }

    #[Test]
    #[DataProvider('realNumbers')]
    public function it_rejects_every_other_checksum_digit(string $value): void
    {
        $this->assertBrokenByEveryOtherChecksum($value);
    }

    #[Test]
    #[DataProvider('malformedNumbers')]
    public function it_rejects_a_malformed_value(string $value): void
    {
        $this->assertFalse(
            Ogrn::isValid($value),
        );
    }

    #[Test]
    #[DataProvider('realNumbers')]
    public function it_rejects_every_other_prefix(string $value): void
    {
        $this->assertRejectedByEveryOtherPrefix($value);
    }

    #[Test]
    public function it_accepts_the_rarer_prefix_of_a_legal_entity(): void
    {
        $this->assertTrue(
            Ogrn::isValid(self::FIVE_PREFIXED_ENTITY),
        );
    }

    #[Test]
    public function it_rejects_a_record_of_the_register_of_legal_entities(): void
    {
        $this->assertSame(
            self::LEGAL_ENTITY_RECORD,
            Ogrn::fromBody(substr(self::LEGAL_ENTITY_RECORD, 0, 12))->value,
        );

        $this->assertFalse(
            Ogrn::isValid(self::LEGAL_ENTITY_RECORD),
        );
    }

    #[Test]
    public function it_rejects_a_record_of_the_register_of_sole_proprietors(): void
    {
        $this->assertSame(
            self::SOLE_PROPRIETOR_RECORD,
            Ogrn::fromBody(substr(self::SOLE_PROPRIETOR_RECORD, 0, 14))->value,
        );

        $this->assertFalse(
            Ogrn::isValid(self::SOLE_PROPRIETOR_RECORD),
        );
    }

    #[Test]
    public function it_builds_a_legal_entity_number_from_its_body(): void
    {
        $this->assertSame(
            '1027700132195',
            Ogrn::fromBody('102770013219')->value,
        );
    }

    #[Test]
    public function it_builds_a_sole_proprietor_number_from_its_body(): void
    {
        $this->assertSame(
            self::SOLE_PROPRIETOR,
            Ogrn::fromBody('30450010000001')->value,
        );

        $this->assertTrue(
            Ogrn::isValid(self::SOLE_PROPRIETOR),
        );
    }

    #[Test]
    public function it_rejects_every_other_checksum_digit_of_a_sole_proprietor_number(): void
    {
        $this->assertBrokenByEveryOtherChecksum(self::SOLE_PROPRIETOR);
    }

    #[Test]
    public function it_rejects_a_body_of_a_wrong_length(): void
    {
        $this->expectException(InvalidRequisite::class);

        Ogrn::fromBody('1234567890');
    }

    #[Test]
    public function it_reads_the_region_and_the_kind(): void
    {
        $entity = Ogrn::from('1027700132195');

        $this->assertSame(
            '77',
            $entity->region()?->value,
        );

        $this->assertFalse(
            $entity->isIndividual(),
        );

        $this->assertTrue(
            Ogrn::from(self::SOLE_PROPRIETOR)->isIndividual(),
        );
    }

    #[Test]
    public function it_reads_the_year_the_record_was_made(): void
    {
        $this->assertSame(
            2002,
            Ogrn::from('1027700132195')->year(),
        );

        $this->assertSame(
            2015,
            Ogrn::from(self::LATER_RECORD)->year(),
        );

        $this->assertSame(
            2004,
            Ogrn::from(self::SOLE_PROPRIETOR)->year(),
        );
    }

    /**
     * Asserts that no digit but the one in place passes as the checksum of the number.
     *
     * @param string $value
     * @return void
     */
    private function assertBrokenByEveryOtherChecksum(string $value): void
    {
        $position = strlen($value) - 1;

        foreach (str_split('0123456789') as $digit) {
            if ($digit === $value[$position]) {
                continue;
            }

            $this->assertFalse(
                Ogrn::isValid(
                    substr_replace($value, $digit, $position, 1),
                ),
            );
        }
    }

    /**
     * Asserts that no prefix but the one of its kind passes, with the checksum recomputed for each.
     *
     * @param string $value
     * @return void
     */
    private function assertRejectedByEveryOtherPrefix(string $value): void
    {
        $allowed = strlen($value) === 13
            ? ['1', '5']
            : ['3'];

        foreach (str_split('0123456789') as $digit) {
            if (in_array($digit, $allowed, true)) {
                continue;
            }

            $body = $digit . substr($value, 1, strlen($value) - 2);

            $this->assertFalse(
                Ogrn::isValid(
                    Ogrn::fromBody($body)->value,
                ),
            );
        }
    }

    /**
     * Registry number of every real organization the checks are built on.
     *
     * @return array<string, array{string}>
     */
    public static function realNumbers(): array
    {
        return [
            'Sberbank' => ['1027700132195'],
            'Gazprom' => ['1027700070518'],
            'Yandex' => ['1027700229193'],
        ];
    }

    /**
     * Values that break the registry number format or checksum.
     *
     * @return array<string, array{string}>
     */
    public static function malformedNumbers(): array
    {
        return [
            'empty' => [''],
            'twelve digits' => ['102770013219'],
            'fourteen digits' => ['10277001321950'],
            'letters' => ['102770013219a'],
        ];
    }
}
