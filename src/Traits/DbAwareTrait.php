<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

use Evirma\Bundle\EssentialsBundle\Service\Db\DbService;
use Symfony\Contracts\Service\Attribute\Required;

trait DbAwareTrait
{
    protected DbService $db;

    #[Required]
    public function setDb(DbService $dbService): void
    {
        $this->db = $dbService;
    }
}
