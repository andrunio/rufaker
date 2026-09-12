<?php

declare(strict_types=1);

namespace RuFaker\Faker;

/**
 * Hands the package to fakerphp/faker under a single name: $faker->ruFaker().
 */
final readonly class RuFakerProvider
{
    /** Values behind the entry point, built once and handed out on every call. */
    private Values $values;

    /**
     * Builds a provider over the given values, by default over ones that follow the Faker seed.
     *
     * @param Values|null $values
     */
    public function __construct(?Values $values = null)
    {
        $this->values = $values ?? new Values();
    }

    /**
     * Answers the only formatter the package registers: everything else hangs off it.
     *
     * @return Values
     */
    public function ruFaker(): Values
    {
        return $this->values;
    }
}
