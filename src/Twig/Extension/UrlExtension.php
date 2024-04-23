<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\Extension;

use Evirma\Bundle\EssentialsBundle\Service\RequestService;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UrlExtension extends AbstractExtension
{
    public function __construct(private readonly RequestService $requestService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('url_save_get', $this->urlSaveGetFilter(...), ['is_safe' => ['all']]),
            new TwigFunction('url_domain', $this->urlDomainFilter(...), ['is_safe' => ['all']]),
        ];
    }

    public function urlSaveGetFilter($replace = array(), $delete = array(), $route = null, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->requestService->urlSaveGet($replace, $delete, $route, $parameters, $referenceType);
    }

    #[ArrayShape(["scheme" => "string", "host" => "string", "port" => "int", "user" => "string", "pass" => "string", "query" => "string", "path" => "string", "fragment" => "string"])] public function urlDomainFilter($url): array
    {
        return (array)parse_url($url, PHP_URL_HOST);
    }
}
