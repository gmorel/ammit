<?php
declare(strict_types = 1);

namespace Imedia\Ammit\UI\Resolver\Validator;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Imedia\Ammit\Domain\MailMxValidation;
use Imedia\Ammit\UI\Resolver\Validator\Implementation\Pragmatic\UuidValidatorTrait;

/**
 * @internal Contains Domain Validation assertions (but class won't be removed in next version)
 *   Domain Validation should be done in Domain
 *   Should be used for prototyping project knowing you are accumulating technical debt
 *
 * @author Guillaume MOREL <g.morel@imediafrance.fr>
 */
class PragmaticRawValueValidator extends RawValueValidator
{
    use UuidValidatorTrait;

    /**
     * Domain should be responsible for id format
     * Exceptions are caught in order to be processed later
     * @param mixed $value String ?
     *
     * @return mixed Untouched value
     */
    public function mustHaveLengthBetween($value, int $min, int $max, string $propertyPath = null, UIValidatorInterface $parentValidator = null, string $exceptionMessage = null)
    {
        $this->validationEngine->validateFieldValue(
            $parentValidator ?: $this,
            function() use ($value, $min, $max, $propertyPath, $exceptionMessage) {
                Assertion::betweenLength(
                    $value,
                    $min,
                    $max,
                    $exceptionMessage,
                    $propertyPath
                );
            }
        );

        return $value;
    }

    /**
     * Domain should be responsible for string emptiness
     * Exceptions are caught in order to be processed later
     * @param mixed $value String not empty ?
     *
     * @return mixed Untouched value
     */
    public function mustBeStringNotEmpty($value, string $propertyPath = null, UIValidatorInterface $parentValidator = null, string $exceptionMessage = null)
    {
        $this->validationEngine->validateFieldValue(
            $parentValidator ?: $this,
            function() use ($value, $propertyPath, $exceptionMessage) {
                Assertion::notEmpty(
                    $value,
                    $exceptionMessage,
                    $propertyPath
                );
            }
        );

        return $value;
    }

    /**
     * Domain should be responsible for email format
     * Exceptions are caught in order to be processed later
     * @param mixed $value Email ?
     *
     * @return mixed Untouched value
     */
    public function mustBeEmailAddress($value, string $propertyPath = null, UIValidatorInterface $parentValidator = null, string $exceptionMessage = null)
    {
        $mailMxValidation = new MailMxValidation();
        $this->validationEngine->validateFieldValue(
            $parentValidator ?: $this,
            function() use ($value, $propertyPath, $exceptionMessage, $mailMxValidation) {
                Assertion::true(
                    $mailMxValidation->isEmailFormatValid($value) && $mailMxValidation->isEmailHostValid($value),
                    $exceptionMessage,
                    $propertyPath
                );
            }
        );

        return $value;
    }

    /**
     * Domain should be responsible for regex validation
     * Exceptions are caught in order to be processed later
     * @param mixed  $value   Valid against Regex ?
     * @param string $pattern Regex
     *
     * @return mixed Untouched value
     */
    public function mustBeValidAgainstRegex($value, string $pattern, string $propertyPath = null, UIValidatorInterface $parentValidator = null, string $exceptionMessage = null)
    {
        $this->validationEngine->validateFieldValue(
            $parentValidator ?: $this,
            function() use ($value, $pattern, $propertyPath, $exceptionMessage) {
                if (preg_match($pattern, $value)) {

                } else {
                    throw new InvalidArgumentException(
                        $exceptionMessage ?: sprintf('Value "%s" not valid against regex "%s".', $value, $pattern),
                        0,
                        $propertyPath,
                        $value
                    );
                }
            }
        );

        return $value;
    }
}
