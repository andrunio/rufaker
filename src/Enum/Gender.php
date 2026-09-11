<?php

declare(strict_types=1);

namespace RuFaker\Enum;

/**
 * Gender of a person, the source of every agreement rule inside a full name.
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    /**
     * Returns the label of this gender in Russian.
     *
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::Male => 'мужской',
            self::Female => 'женский',
        };
    }

    /**
     * Tells whether the name of a person of this gender takes masculine forms.
     *
     * @return bool
     */
    public function isMale(): bool
    {
        return $this === self::Male;
    }

    /**
     * Tells whether the name of a person of this gender takes feminine forms.
     *
     * @return bool
     */
    public function isFemale(): bool
    {
        return $this === self::Female;
    }
}
