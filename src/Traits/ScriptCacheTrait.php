<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

trait ScriptCacheTrait
{
    private array $scriptCache = [];

    public function getScriptCache(string $id): mixed
    {
        return $this->scriptCache[$id] ?? null;
    }

    public function setScriptCache(string $id, mixed $data): void
    {
        $this->scriptCache[$id] = $data;
    }

    public function deleteScriptCache(string|null $id = null): void
    {
        if (is_null($id)) {
            $this->scriptCache = [];
        } else {
            unset($this->scriptCache[$id]);
        }
    }
}
