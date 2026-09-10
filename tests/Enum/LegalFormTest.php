<?php

declare(strict_types=1);

namespace RuFaker\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\LegalForm;

#[CoversClass(LegalForm::class)]
final class LegalFormTest extends TestCase
{
    #[Test]
    #[DataProvider('legalEntities')]
    public function it_describes_a_legal_entity(LegalForm $form): void
    {
        $this->assertSame(
            10,
            $form->innDigits(),
        );

        $this->assertSame(
            1,
            $form->innChecksumDigits(),
        );

        $this->assertSame(
            13,
            $form->registryNumberDigits(),
        );

        $this->assertSame(
            '1',
            $form->registryNumberPrefix(),
        );

        $this->assertSame(
            '40702',
            $form->balanceAccount(),
        );

        $this->assertTrue(
            $form->hasKpp(),
        );

        $this->assertFalse(
            $form->isIndividual(),
        );
    }

    #[Test]
    public function it_describes_a_sole_proprietor(): void
    {
        $form = LegalForm::Ip;

        $this->assertSame(
            12,
            $form->innDigits(),
        );

        $this->assertSame(
            2,
            $form->innChecksumDigits(),
        );

        $this->assertSame(
            15,
            $form->registryNumberDigits(),
        );

        $this->assertSame(
            '3',
            $form->registryNumberPrefix(),
        );

        $this->assertSame(
            '40802',
            $form->balanceAccount(),
        );

        $this->assertFalse(
            $form->hasKpp(),
        );

        $this->assertTrue(
            $form->isIndividual(),
        );
    }

    #[Test]
    public function it_tells_the_two_kinds_apart_by_the_kpp_alone(): void
    {
        foreach (LegalForm::cases() as $form) {
            $this->assertSame(
                $form->hasKpp(),
                !$form->isIndividual(),
            );
        }
    }

    /**
     * Every form the enum treats as a legal entity rather than a person.
     *
     * @return array<string, array{LegalForm}>
     */
    public static function legalEntities(): array
    {
        return [
            'limited liability company' => [LegalForm::Ooo],
            'joint-stock company' => [LegalForm::Ao],
            'public joint-stock company' => [LegalForm::Pao],
        ];
    }
}
