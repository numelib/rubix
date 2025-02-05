<?php

namespace App\Validator;

use App\Entity\Contact;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ContactProgramPostingValidator extends ConstraintValidator
{
    /**
     * @param Contact $contact
     */
    public function validate($contact, Constraint $constraint): void
    {
        if (!$contact instanceof Contact) {
            throw new UnexpectedValueException($contact, Contact::class);
        }

        if ($contact->getProgramSent() && !$contact->getProgramPosting()) {
            $this->context
                ->buildViolation('Merci d\'indiquer à quelle adresse ce contact reçoit le programme.')
                ->atPath('contact.programSent')
                ->addViolation();
        }

        if ($contact->getProgramSent() && $contact->getProgramPosting()) {
            if($contact->getProgramPosting()->getAddressType() === 'professional' && !$contact->getProgramPosting()->getStructure())
            $this->context
                ->buildViolation('Merci d\'indiquer à quelle structure adresser le programme.')
                ->atPath('contact.programSent')
                ->addViolation();
        }
    }
}