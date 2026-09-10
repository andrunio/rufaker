<?php

declare(strict_types=1);

namespace RuFaker\Result;

use RuFaker\Enum\LegalForm;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\ArrayValue;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;

/**
 * Requisites of one business that agree with each other.
 */
final readonly class Organization implements Result
{
    use ArrayValue;

    /**
     * Assembles business requisites, rejecting a set that contradicts itself.
     *
     * @param LegalForm $form
     * @param Region $region
     * @param Inn $inn
     * @param Ogrn $ogrn
     * @param Kpp|null $kpp
     * @param Person|null $person
     * @throws InvalidRequisite
     */
    public function __construct(
        public LegalForm $form,
        public Region    $region,
        public Inn       $inn,
        public Ogrn      $ogrn,
        public ?Kpp      $kpp = null,
        public ?Person   $person = null,
    )
    {
        $innDigits = $form->innDigits();
        $registryDigits = $form->registryNumberDigits();

        if (strlen($inn->value) !== $innDigits) {
            throw InvalidRequisite::because("INN of $form->value must be $innDigits digits long.");
        }

        if (strlen($ogrn->value) !== $registryDigits) {
            throw InvalidRequisite::because("Registry number of $form->value must be $registryDigits digits long.");
        }

        if ($form->hasKpp() !== ($kpp instanceof Kpp)) {
            throw InvalidRequisite::because("Presence of a KPP contradicts the legal form $form->value.");
        }

        if ($form->isIndividual() !== ($person instanceof Person)) {
            throw InvalidRequisite::because("Presence of a full name contradicts the legal form $form->value.");
        }

        if ($inn->region()?->value !== $region->value) {
            throw InvalidRequisite::because("INN $inn->value does not belong to region $region->value.");
        }

        if ($ogrn->region()?->value !== $region->value) {
            throw InvalidRequisite::because("Registry number $ogrn->value does not belong to region $region->value.");
        }

        if ($kpp instanceof Kpp && $kpp->region()?->value !== $region->value) {
            throw InvalidRequisite::because("KPP $kpp->value does not belong to region $region->value.");
        }
    }

    /**
     * Returns the requisites as plain strings, ready for a fixture or a payload.
     *
     * @return array{form: string, region: string, inn: string, ogrn: string, kpp: string|null, person: string|null}
     */
    public function toArray(): array
    {
        return [
            'form' => $this->form->value,
            'region' => $this->region->value,
            'inn' => $this->inn->value,
            'ogrn' => $this->ogrn->value,
            'kpp' => $this->kpp?->value,
            'person' => $this->person?->full(),
        ];
    }
}
