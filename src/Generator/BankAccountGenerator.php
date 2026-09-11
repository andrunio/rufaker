<?php

declare(strict_types=1);

namespace RuFaker\Generator;

use Random\Randomizer;
use RuFaker\Enum\LegalForm;
use RuFaker\Internal\TitleBook;
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;
use RuFaker\Result\BankAccount;

/**
 * Builds payment details whose accounts are keyed to the BIK of their own bank.
 */
final readonly class BankAccountGenerator
{
    /** Alphabet a random digit sequence is drawn from. */
    private const string DIGITS = '0123456789';

    /** Country prefix shared by every participant of the Russian payment system. */
    private const string COUNTRY = '04';

    /** Balance account a bank holds at a Bank of Russia division. */
    private const string CORRESPONDENT_BALANCE = '30101';

    /** Currency code of the rouble. */
    private const string ROUBLE = '810';

    /** Written where the control key belongs until it is computed. */
    private const string KEY_PLACEHOLDER = '0';

    /** Bank division inside a correspondent account number, always zeros. */
    private const string CENTRAL_BANK_DIVISION = '00000000';

    /** Bank division inside a customer account number. */
    private const string BANK_DIVISION = '0000';

    /** Participant numbers below this value are reserved by the Bank of Russia. */
    private const int FIRST_PARTICIPANT = 50;

    /** Forms a credit organization is founded in: a business company, never a sole proprietor. */
    private const array FORMS = [
        LegalForm::Ooo,
        LegalForm::Ao,
        LegalForm::Pao,
    ];

    /**
     * Builds a generator drawing from the given randomizer.
     *
     * @param Randomizer $randomizer
     */
    public function __construct(private Randomizer $randomizer)
    {
    }

    /**
     * Builds a bank together with its correspondent account and one customer account.
     *
     * @param Bik|null $bik
     * @param LegalForm|null $form
     * @return BankAccount
     */
    public function generate(?Bik $bik = null, ?LegalForm $form = null): BankAccount
    {
        $bik ??= $this->bik();

        return new BankAccount(
            $bik,
            $this->correspondent($bik),
            $this->settlement($bik, $form ?? LegalForm::Ooo),
            $this->bankForm($bik),
            $this->title($bik),
        );
    }

    /**
     * Builds a standalone BIK.
     *
     * @return Bik
     */
    public function bik(): Bik
    {
        // Territory and Bank of Russia division take two digits each, the participant number three.
        return Bik::from(self::COUNTRY . $this->digits(4) . $this->participant());
    }

    /**
     * Builds the account the bank holds at a Bank of Russia division.
     *
     * @param Bik $bik
     * @return Account
     */
    private function correspondent(Bik $bik): Account
    {
        // A correspondent account ends with the participant number of the bank holding it.
        return Account::fromDraft(
            self::CORRESPONDENT_BALANCE
            . self::ROUBLE
            . self::KEY_PLACEHOLDER
            . self::CENTRAL_BANK_DIVISION
            . $bik->participant(),
            $bik,
        );
    }

    /**
     * Builds a customer account opened for the given legal form.
     *
     * @param Bik $bik
     * @param LegalForm $form
     * @return Account
     */
    private function settlement(Bik $bik, LegalForm $form): Account
    {
        return Account::fromDraft(
            $form->balanceAccount()
            . self::ROUBLE
            . self::KEY_PLACEHOLDER
            . self::BANK_DIVISION
            . $this->digits(7),
            $bik,
        );
    }

    /**
     * Reads the legal form of the bank off its own identifier.
     *
     * @param Bik $bik
     * @return LegalForm
     */
    private function bankForm(Bik $bik): LegalForm
    {
        // The form follows the territory code, so the same BIK is never a different bank.
        return self::FORMS[(int)$bik->territory() % count(self::FORMS)];
    }

    /**
     * Reads the proper name of the bank off its own identifier.
     *
     * @param Bik $bik
     * @return string
     */
    private function title(Bik $bik): string
    {
        $titles = TitleBook::titles();

        // The name follows the participant number, and both parts of a BIK stay put.
        return $titles[(int)$bik->participant() % count($titles)];
    }

    /**
     * Picks a participant number outside the range reserved by the Bank of Russia.
     *
     * @return string
     */
    private function participant(): string
    {
        return sprintf('%03d', $this->randomizer->getInt(self::FIRST_PARTICIPANT, 999));
    }

    /**
     * Draws a random digit string of the given length.
     *
     * @param int $length
     * @return string
     */
    private function digits(int $length): string
    {
        return $this->randomizer->getBytesFromString(self::DIGITS, $length);
    }
}
