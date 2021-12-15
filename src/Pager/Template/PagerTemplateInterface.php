<?php

namespace Evirma\Bundle\EssentialsBundle\Pager\Template;

use Evirma\Bundle\EssentialsBundle\Pager\Pager;

interface PagerTemplateInterface
{
    public function render(Pager $pager, callable $routeGenerator, array $options = []): string;
    public function getName(): string;
}
