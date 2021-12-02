<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

use Psr\Container\ContainerInterface;
use LogicException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @property ContainerInterface $container
 */
trait ValidatorTrait
{
    protected ValidatorInterface $validatorManager;

    #[Required]
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validatorManager = $validator;
    }

    protected function getValidator(): ValidatorInterface
    {
        if (!isset($this->validatorManager)) {
            throw new LogicException('ValidatorInterface must be defined');
        }

        return $this->validatorManager;
    }

    /**
     * @param mixed                                                 $value       The value to validate
     * @param Constraint|Constraint[]|null                          $constraints The constraint(s) to validate against
     * @param string|GroupSequence|array<string|GroupSequence>|null $groups      The validation groups to validate. If none is given, "Default" is assumed
     */
    protected function validateValue(mixed $value, mixed $constraints = null, mixed $groups = null): ConstraintViolationListInterface
    {
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        /** @var ConstraintViolationList|ConstraintViolation[] $violationList */
        $violationList = $this->getValidator()->validate($value, $constraints, $groups);

        return $violationList;
    }

    /**
     * @param mixed                                                 $value       The value to validate
     * @param Constraint|Constraint[]|null                          $constraints The constraint(s) to validate against
     * @param string|GroupSequence|array<string|GroupSequence>|null $groups      The validation groups to validate. If none is given, "Default" is assumed
     * @return bool|string|null
     */
    protected function isInvalidValue(mixed $value, array|Constraint $constraints = null, array|GroupSequence|string $groups = null): bool|string|null
    {
        $violationList = $this->validateValue($value, $constraints, $groups);
        foreach ($violationList as $violation) break;
        if (isset($violation)) return $violation->getMessage();
        return false;
    }


    /**
     * @param mixed                                                 $value       The value to validate
     * @param Constraint|Constraint[]|null                          $constraints The constraint(s) to validate against
     * @param string|GroupSequence|array<string|GroupSequence>|null $groups      The validation groups to validate. If none is given, "Default" is assumed
     * @return bool
     */
    protected function isValidValue(mixed $value, array|Constraint $constraints = null, array|GroupSequence|string $groups = null): bool
    {
        $violationList = $this->validateValue($value, $constraints, $groups);
        foreach ($violationList as $violation) break;
        return !isset($violation);
    }
}
