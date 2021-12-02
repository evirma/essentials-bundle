<?php

declare(strict_types=1);

namespace Evirma\Bundle\CoreBundle\Traits;

trait TransformerMappingTrait
{
    public array $groups = [];

    public function getGroups(): array
    {
        if (empty($this->groups)) {
            $this->groups = ['Default'];
        }

        return $this->groups;
    }

    public function setGroups(array $groups = []): static
    {
        $this->groups = $groups;

        return $this;
    }

    public function reverseTransform(mixed $value): mixed
    {
        return $value;
    }
}
