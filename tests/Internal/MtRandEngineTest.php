<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
use RuFaker\Internal\MtRandEngine;

#[CoversClass(MtRandEngine::class)]
final class MtRandEngineTest extends TestCase
{
    #[Test]
    public function it_yields_a_word_of_four_bytes(): void
    {
        $this->assertSame(
            4,
            strlen((new MtRandEngine())->generate()),
        );
    }

    #[Test]
    public function it_repeats_itself_after_the_same_seed(): void
    {
        mt_srand(1234, MT_RAND_MT19937);
        $first = (new Randomizer(new MtRandEngine()))
            ->getBytesFromString('0123456789', 12);

        mt_srand(1234, MT_RAND_MT19937);

        $this->assertSame(
            $first,
            (new Randomizer(new MtRandEngine()))
                ->getBytesFromString('0123456789', 12),
        );
    }

    #[Test]
    public function it_moves_on_with_every_word(): void
    {
        mt_srand(1234, MT_RAND_MT19937);
        $engine = new MtRandEngine();

        $this->assertNotSame(
            $engine->generate(),
            $engine->generate(),
        );
    }
}
