<?php

namespace Evirma\Bundle\EssentialsBundle\Pager\Twig;

use Evirma\Bundle\EssentialsBundle\Locale\Locale;
use Evirma\Bundle\EssentialsBundle\Pager\Pager;
use Evirma\Bundle\EssentialsBundle\Pager\RouteGenerator\RouterRouteGenerator;
use Evirma\Bundle\EssentialsBundle\Pager\Template\PagerTemplateBem;
use Evirma\Bundle\EssentialsBundle\Pager\Template\PagerTemplateDefault;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PagerExtension extends AbstractExtension
{
    private RequestStack $requestStack;
    private UrlGeneratorInterface $router;
    private Locale $locale;

    public function __construct(UrlGeneratorInterface $router, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
        if ($requestStack->getCurrentRequest()) {
            $this->locale = $requestStack->getCurrentRequest()->getLocale();
        } else {
            $this->locale = 'en';
        }
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('super_pager', [$this, 'renderPager'], ['is_safe' => ['html']]),
            new TwigFunction('super_pager_page_url', [$this, 'getPageUrl']),
        ];
    }

    public function renderPager(Pager $pager, string $viewName = null, array $options = []): string
    {
        $locale = $options['locale'] ?? null;
        return $this->getPagerTemplateByName($viewName, $locale)->render($pager, $this->createRouteGenerator($options), $options);
    }

    /**
     * @param Pager $pager
     * @param int   $page
     * @param array $options
     * @return string
     */
    public function getPageUrl(Pager $pager, int $page, array $options = []): string
    {
        if ($page < 0 || $page > $pager->getPages()) {
            throw new InvalidArgumentException("Page '$page' is out of bounds");
        }

        $routeGenerator = $this->createRouteGenerator($options);
        return $routeGenerator($page);
    }

    public function createRouteGenerator(array $options = []): RouterRouteGenerator
    {
        $options = array_replace(
            [
                'route' => null,
                'route_params' => [],
                'page_parameter' => '[page]',
                'omit_first_page' => true,
            ],
            $options
        );

        if (null === $options['route']) {
            $request = $this->requestStack->getCurrentRequest();

            if (null !== $this->requestStack->getParentRequest()) {
                throw new RuntimeException('The route generator can not guess the route when used in a sub-request, pass the "route" option to use this generator.');
            }

            $options['route'] = $request->attributes->get('_route');

            // Make sure we read the route parameters from the passed option array
            $defaultRouteParams = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

            $options['route_params'] = array_merge($defaultRouteParams, $options['route_params']);
        }

        return new RouterRouteGenerator($this->router, $options);
    }

    #[Pure] private function getPagerTemplateByName($name, $locale = null): PagerTemplateDefault|PagerTemplateBem
    {
        if (!$locale) {
            $locale = $this->locale;
        }

        return match ($name) {
            'bem' => new PagerTemplateBem($locale),
            default => new PagerTemplateDefault($locale),
        };
    }
}