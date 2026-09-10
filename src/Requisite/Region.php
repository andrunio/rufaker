<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use Random\Randomizer;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\Digits;
use RuFaker\Internal\StringValue;

/**
 * Two-digit code of a federal subject, the leading part of an INN, a KPP and a registry number.
 */
final readonly class Region implements Requisite
{
    use StringValue;

    /** Codes a generator picks from when no region is given. */
    private const array POOL = [
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

    /**
     * Wraps a region code, rejecting anything outside the 01-99 range.
     *
     * @param string $value
     * @return self
     * @throws InvalidRequisite
     */
    public static function from(string $value): self
    {
        return self::tryFrom($value)
            ?? throw InvalidRequisite::for('region code', $value);
    }

    /**
     * Wraps a region code, returning null instead of throwing.
     *
     * @param string $value
     * @return self|null
     */
    public static function tryFrom(string $value): ?self
    {
        return self::isValid($value)
            ? new self($value)
            : null;
    }

    /**
     * Tells whether the code can address a federal subject.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return Digits::areDigits($value, 2) && $value !== '00';
    }

    /**
     * Lists the codes used for generation; every code of the 01-99 range is still accepted.
     *
     * @return list<string>
     */
    public static function pool(): array
    {
        return self::POOL;
    }

    /**
     * Picks a region out of the generation pool.
     *
     * @param Randomizer $randomizer
     * @return self
     */
    public static function random(Randomizer $randomizer): self
    {
        return new self(
            self::POOL[$randomizer->getInt(0, count(self::POOL) - 1)],
        );
    }
}
