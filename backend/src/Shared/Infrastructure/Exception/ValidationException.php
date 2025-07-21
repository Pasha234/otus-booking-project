<?php

namespace App\Shared\Infrastructure\Exception;

use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends ValidationFailedException
{
    public function __construct(object $violatingObject, ConstraintViolationListInterface $violations)
    {
        parent::__construct($violatingObject, $violations);
    }

    public function getFormattedErrors(): array
    {
        $formErrors = [];
        foreach ($this->getViolations() as $violation) {
            $formErrors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $formErrors;
    }
}