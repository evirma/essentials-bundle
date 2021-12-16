<?php

namespace Evirma\Bundle\EssentialsBundle\Pager\Template;

use Evirma\Bundle\EssentialsBundle\i18n\Locale;
use InvalidArgumentException;
use RuntimeException;
use function gettype;
use function is_callable;

abstract class AbstractPagerTemplate implements PagerTemplateInterface
{
    protected array $options = [];

    /**
     * @var callable|null
     */
    private mixed $routeGenerator = null;

    public function __construct(protected Locale $locale)
    {
    }

    protected function option(string $name): mixed
    {
        if (!isset($this->options[$name])) {
            throw new InvalidArgumentException(sprintf('The option "%s" does not exist.', $name));
        }

        return $this->options[$name];
    }

    protected function generateRoute(int $page): string
    {
        $generator = $this->getRouteGenerator();

        return $generator($page);
    }

    protected function getRouteGenerator(): callable
    {
        if (!$this->routeGenerator) {
            throw new RuntimeException(sprintf('The route generator was not set to the template, ensure you call %s::setRouteGenerator().', static::class));
        }

        return $this->routeGenerator;
    }

    public function setRouteGenerator(callable $routeGenerator): void
    {
        if (!is_callable($routeGenerator)) {
            throw new InvalidArgumentException(sprintf('The $routeGenerator argument of %s() must be a callable, a %s was given.', __METHOD__, gettype($routeGenerator)));
        }

        $this->routeGenerator = $routeGenerator;
    }
}
