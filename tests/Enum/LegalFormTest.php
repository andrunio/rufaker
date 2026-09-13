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
            2,
            $form->registryNumberFirstYear(),
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

        $this->assertTrue(
            $form->isCorporate(),
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
            4,
            $form->registryNumberFirstYear(),
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

        $this->assertFalse(
            $form->isCorporate(),
        );
    }

    #[Test]
    public function it_tells_the_two_kinds_apart(): void
    {
        foreach (LegalForm::cases() as $form) {
            $this->assertSame(
                $form->isCorporate(),
                !$form->isIndividual(),
            );

            $this->assertSame(
                $form->hasKpp(),
                $form->isCorporate(),
            );
        }
    }

    #[Test]
    public function it_names_itself_in_russian(): void
    {
        $this->assertSame(
            [
                'ООО',
                'АО',
                'ПАО',
                'ИП',
            ],
            array_map(
                static fn(LegalForm $form): string => $form->shortTitle(),
                LegalForm::cases(),
            ),
        );

        $this->assertSame(
            [
                'Общество с ограниченной ответственностью',
                'Акционерное общество',
                'Публичное акционерное общество',
                'Индивидуальный предприниматель',
            ],
            array_map(
                static fn(LegalForm $form): string => $form->fullTitle(),
                LegalForm::cases(),
            ),
        );
    }

    #[Test]
    public function it_carries_values_a_payload_can_hold(): void
    {
        $this->assertSame(
            [
                'ooo',
                'ao',
                'pao',
                'ip',
            ],
            array_map(
                static fn(LegalForm $form): string => $form->value,
                LegalForm::cases(),
            ),
        );
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
