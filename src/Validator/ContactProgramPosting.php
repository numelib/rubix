<?php

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ContactProgramPosting extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}