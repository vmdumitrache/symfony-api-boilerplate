<?php

declare(strict_types=1);

namespace App\Util\Handler;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ErrorMessageHandler.
 *
 * @author Vlad Dumitrache <vlad@vmdumitrache.dev>
 */
class ErrorMessageHandler
{
    /**
     * @return array|false
     */
    public function getValidationErrors(ConstraintViolationListInterface $violations = null)
    {
        if (empty($violations)) {
            return false;
        }
        $errors = [];
        foreach ($violations as $field => $violation) {
            $fieldName = str_replace(['[', ']'], '', $violation->getPropertyPath());
            if (!empty($fieldName)) {
                $errors[$fieldName][] = $violation->getMessage();
            }
        }

        return $errors;
    }
}
