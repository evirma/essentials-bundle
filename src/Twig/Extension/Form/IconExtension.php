<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\Extension\Form;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class IconExtension extends AbstractExtension
{
    private string $tag;

    public function __construct(string $iconTag = 'i')
    {
        $this->tag = $iconTag;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('parse_icons', [$this, 'parseIconsFilter'], ['pre_escape' => 'html', 'is_safe' => array('html')])
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('icon', [$this, 'getIconFilter'], ['pre_escape' => 'html', 'is_safe' => array('html')])
        ];
    }

    public function parseIconsFilter($text): string
    {
        $that = $this;

        $test = substr($text, 0, 5);
        if (in_array($test, ['.fal ', '.fas ', '.far ', '.fab '])) {
            return sprintf('<%1$s class="%2$s"></%1$s>', $this->tag, substr($text, 1));
        }

        return (string)preg_replace_callback(
            '/\.([a-z]+-[a-z\d+-]+)/',
            function ($matches) use ($that) {
                return $that->getIconFilter($matches[1]);
            },
            $text
        );
    }

    public function getIconFilter($icon): string
    {
        if (strpos($icon, '-')) {
            [$namespace, $iconName] = explode('-', $icon, 2);
        } else {
            $iconName = $icon;
            $namespace = null;
        }

        $class = match ($namespace) {
            'fal', 'fa' => 'fal fa-fw fa-'.$iconName,
            'far' => 'far fa-fw fa-'.$iconName,
            'fas' => 'fas fa-fw fa-'.$iconName,
            'fab' => 'fab fa-fw fa-'.$iconName,
            default => 'fal fa-fw fa-'.$icon,
        };

        return sprintf('<%1$s class="%2$s"></%1$s>', $this->tag, $class);
    }
}
