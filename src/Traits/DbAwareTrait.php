<?php

declare(strict_types=1);

namespace Evirma\Bundle\CoreBundle\Traits;

use Evirma\Bundle\CoreBundle\Service\Db\DbService;
use Symfony\Contracts\Service\Attribute\Required;

trait DbAwareTrait
{
    protected DbService $db;

    #[Required]
    public function setDb(DbService $dbService)
    {
        $this->db = $dbService;
    }
}
