<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Internal\TitleBook;

#[CoversClass(TitleBook::class)]
final class TitleBookTest extends TestCase
{
    #[Test]
    public function it_holds_proper_names_to_choose_from(): void
    {
        $this->assertNotEmpty(
            TitleBook::titles(),
        );
    }

    #[Test]
    public function it_repeats_no_title(): void
    {
        $titles = TitleBook::titles();

        $this->assertSame(
            $titles,
            array_values(array_unique($titles)),
        );
    }

    #[Test]
    public function it_holds_one_word_per_title(): void
    {
        foreach (TitleBook::titles() as $title) {
            $this->assertMatchesRegularExpression(
                '/^[А-ЯЁ][а-яё]+$/u',
                $title,
            );
        }
    }
}
