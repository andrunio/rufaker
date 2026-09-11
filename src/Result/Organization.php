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

    /** Short label of the legal form the requisites are built for. */
    public string $form;

    /** Name the way it is written on a stamp: the short form of the business. */
    public string $shortName;

    /** Name the way it is written in the registry: the full form of the business. */
    public string $fullName;

    /** Code of the federal subject every requisite belongs to. */
    public string $region;

    /** Taxpayer number. */
    public string $inn;

    /** State registry number. */
    public string $ogrn;

    /** Tax registration reason code; a sole proprietor has none. */
    public ?string $kpp;

    /** Legal form as the enum case it came from. */
    private LegalForm $formType;

    /** Region as the requisite it came from. */
    private Region $regionType;

    /** Taxpayer number as the requisite it came from. */
    private Inn $innType;

    /** State registry number as the requisite it came from. */
    private Ogrn $ogrnType;

    /** Tax registration reason code as the requisite it came from. */
    private ?Kpp $kppType;

    /** Full name of the sole proprietor; a legal entity has none. */
    private ?Person $personType;

    /**
     * Assembles business requisites, rejecting a set that contradicts itself.
     *
     * @param LegalForm $form
     * @param Region $region
     * @param Inn $inn
     * @param Ogrn $ogrn
     * @param Kpp|null $kpp
     * @param Person|null $person
     * @param string|null $title
     * @param bool $initials
     * @throws InvalidRequisite
     */
    public function __construct(
        LegalForm $form,
        Region    $region,
        Inn       $inn,
        Ogrn      $ogrn,
        ?Kpp      $kpp = null,
        ?Person   $person = null,
        ?string   $title = null,
        bool      $initials = false,
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

        if ($form->isIndividual() === ($title !== null)) {
            throw InvalidRequisite::because("Presence of a title contradicts the legal form $form->value.");
        }

        if ($title !== null && trim($title) === '') {
            throw InvalidRequisite::because('A business must have a title.');
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

        $this->formType = $form;
        $this->regionType = $region;
        $this->innType = $inn;
        $this->ogrnType = $ogrn;
        $this->kppType = $kpp;
        $this->personType = $person;

        $this->form = $form->shortTitle();
        $this->shortName = self::buildShortName($form, $person, $title, $initials);
        $this->fullName = self::buildFullName($form, $person, $title);
        $this->region = $region->value;
        $this->inn = $inn->value;
        $this->ogrn = $ogrn->value;
        $this->kpp = $kpp?->value;
    }

    /**
     * Returns the legal form as an enum case.
     *
     * @return LegalForm
     */
    public function form(): LegalForm
    {
        return $this->formType;
    }

    /**
     * Returns the region as a requisite.
     *
     * @return Region
     */
    public function region(): Region
    {
        return $this->regionType;
    }

    /**
     * Returns the taxpayer number as a requisite.
     *
     * @return Inn
     */
    public function inn(): Inn
    {
        return $this->innType;
    }

    /**
     * Returns the state registry number as a requisite.
     *
     * @return Ogrn
     */
    public function ogrn(): Ogrn
    {
        return $this->ogrnType;
    }

    /**
     * Returns the tax registration reason code as a requisite.
     *
     * @return Kpp|null
     */
    public function kpp(): ?Kpp
    {
        return $this->kppType;
    }

    /**
     * Returns the full name of the sole proprietor.
     *
     * @return Person|null
     */
    public function person(): ?Person
    {
        return $this->personType;
    }

    /**
     * Builds the short name: the abbreviation of the form and what stands after it.
     *
     * @param LegalForm $form
     * @param Person|null $person
     * @param string|null $title
     * @param bool $initials
     * @return string
     */
    private static function buildShortName(
        LegalForm $form,
        ?Person   $person,
        ?string   $title,
        bool      $initials,
    ): string
    {
        if (!$person instanceof Person) {
            return $form->shortTitle() . " \"$title\"";
        }

        if ($initials) {
            return $form->shortTitle() . ' ' . self::withInitials($person);
        }

        return $form->shortTitle() . " $person->fullName";
    }

    /**
     * Builds the full name: the form spelled out and what stands after it.
     *
     * @param LegalForm $form
     * @param Person|null $person
     * @param string|null $title
     * @return string
     */
    private static function buildFullName(LegalForm $form, ?Person $person, ?string $title): string
    {
        return $person instanceof Person
            ? $form->fullTitle() . " $person->fullName"
            : $form->fullTitle() . " \"$title\"";
    }

    /**
     * Builds the name of a sole proprietor with the first name and the patronymic cut to initials.
     *
     * @param Person $person
     * @return string
     */
    private static function withInitials(Person $person): string
    {
        return $person->lastName
            . ' ' . self::firstLetter($person->firstName)
            . '.' . self::firstLetter($person->patronymic)
            . '.';
    }

    /**
     * Reads the first letter of a name part, counting letters rather than bytes.
     *
     * @param string $part
     * @return string
     */
    private static function firstLetter(string $part): string
    {
        return preg_match('/^./u', $part, $matches) === 1
            ? $matches[0]
            : '';
    }

    /**
     * Returns the requisites as plain strings, ready for a fixture or a payload.
     *
     * @return array{
     *     form: string,
     *     short_name: string,
     *     full_name: string,
     *     region: string,
     *     inn: string,
     *     ogrn: string,
     *     kpp: string|null,
     *     person: string|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'form' => $this->form,
            'short_name' => $this->shortName,
            'full_name' => $this->fullName,
            'region' => $this->region,
            'inn' => $this->inn,
            'ogrn' => $this->ogrn,
            'kpp' => $this->kpp,
            'person' => $this->personType?->fullName,
        ];
    }
}
