<?php /** @noinspection SpellCheckingInspection */

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
readonly class RequestService
{
    public function __construct(private RequestStack $requestStack, private RouterInterface $router)
    {
    }

    public function getRequestUri(): string
    {
        return $this->requestStack->getMainRequest()->getRequestUri();
    }

    /**
     * Ключи принимаются и в формате filter[search][query][value], и в формате filter%5Bsearch%5D%5Bquery%5D%5Bvalue%5D
     * Если URL имеет вид <..>/?filter[search][query][value]=iphone&filter[search][rubric][value]=343,
     * то ключи и значения подставляются не кодированными, сохраняя общий стиль записи
     * Если URL имеет вид <..>/?filter%5Bsearch%5D%5Bquery%5D%5Bvalue%5D=iphone&filter%5Bsearch%5D%5Brubric%5D%5Bvalue%5D=343,
     * то ключи и значения кодируются посредством urlencode()
     *
     * @param array       $replace Ассоциативный массив ключей и значений для замены get-параметров
     * @param array       $delete  Массив ключей для удаления get-параметров
     * @param string|null $route
     * @param array       $parameters
     * @param int         $referenceType
     * @return string
     */
    public function urlSaveGet(array $replace = [], array $delete = [], ?string $route = null, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if (!$route) {
            $link = $this->getRequestUri();
        } else {
            try {
                $link = $this->router->generate($route, $parameters, $referenceType);
            } catch (RouteNotFoundException) {
                $link = $route;
            }
        }

        $isLinkDecoded = ($link == urldecode($link));

        if ($replace) {
            foreach ($replace as $k => $v) {
                $k = strval($k);
                $k = urldecode($k);
                $v = strval($v);
                $v = urldecode($v);

                if (!$isLinkDecoded) {
                    $k = urlencode($k);
                    $v = urlencode($v);
                }
                if (preg_match('/([&?])'.preg_quote($k)."=[^&]*/i", $link)) {
                    $link = preg_replace('/([&?])'.preg_quote($k)."=[^&]*/i", "\\1".$k."=".$v, $link);
                } else {
                    $link .= "&".$k."=".$v;
                }
            }
        }

        if ($delete) {
            foreach ($delete as $k) {
                $k = strval($k);
                $k = urldecode($k);

                if (!$isLinkDecoded) {
                    $k = urlencode($k);
                }
                $link = preg_replace('/([&?])'.preg_quote($k)."=[^&]*&?/i", "\\1", $link);
            }
            if (str_ends_with($link, '&')) {
                $link = substr($link, 0, -1);
            }
        }

        if (!str_contains($link, '?') && str_contains($link, '&')) {
            $ampPos = strpos($link, '&');
            $link = substr_replace($link, '?', $ampPos, 1);
        }

        $link = rtrim($link, "?");

        if (str_contains($link, '?') && (str_contains($link, '%2f') || str_contains($link, '%2F'))) {
            [$_link, $_query] = explode('?', $link);
            $link = str_replace(['%2f', '%2F'], '/', $_link).'?'.$_query;
        } elseif (str_contains($link, '%2f') || str_contains($link, '%2F')) {
            $link = str_replace(array('%2f', '%2F'), '/', $link);
        }

        return $link;
    }
}
