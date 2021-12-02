<?php

namespace Evirma\Bundle\EssentialsBundle\Pager\Template;

use Evirma\Bundle\EssentialsBundle\Pager\Pager;

interface PagerTemplateInterface
{
    /**
     * The route generator can be any callable to generate the routes receiving the page number than first and unique argument.
     *
     * @param Pager    $pager
     * @param callable $routeGenerator
     * @param array    $options
     * @return string
     */
    public function render(Pager $pager, callable $routeGenerator, array $options = []): string;

    /**
     * Returns the canonical name.
     *
     * @return string
     */
    public function getName(): string;
}
