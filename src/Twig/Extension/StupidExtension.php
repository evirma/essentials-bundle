<?php /** @noinspection SpellCheckingInspection */

namespace Evirma\Bundle\EssentialsBundle\Twig\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Evirma\Bundle\EssentialsBundle\Twig\TokenParser\HeadtagTokenParser;
use Evirma\Bundle\EssentialsBundle\Twig\TokenParser\NoindexTokenParser;
use Evirma\Bundle\EssentialsBundle\Twig\TokenParser\SwitchTokenParser;
use Evirma\Bundle\EssentialsBundle\Util\StringUtil;
use Evirma\Bundle\EssentialsBundle\Util\YamlUtil;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class StupidExtension extends AbstractExtension
{
    private array $storage = [];

    public function getFilters(): array
    {
        return [
            # Strings
            new TwigFilter('lcfirst', StringUtil::lcfirst(...)),
            new TwigFilter('ucfirst', StringUtil::ucfirst(...)),
            new TwigFilter('ucwords', StringUtil::ucwords(...)),

            new TwigFilter('humansize', StringUtil::humanSize(...)),

            new TwigFilter('ltrim', ['ltrim']),
            new TwigFilter('rtrim', ['rtrim']),

            new TwigFilter('contains', [StringUtil::class, 'contains']),
            new TwigFilter('ends_with', [StringUtil::class, 'endsWith']),
            new TwigFilter('starts_with', [StringUtil::class, 'startsWith']),

            new TwigFilter('truncate', StringUtil::safeTruncate(...)),
            new TwigFilter('truncate_html', [StringUtil::class, 'safeTruncateHtml']),

            # Arrays
            new TwigFilter('fieldName', $this->fieldNameFilter(...)),
            new TwigFilter('array_unique', 'array_unique'),

            # Hash and decodes
            new TwigFilter('sha1', $this->sha1(...)),
            new TwigFilter('md5', $this->md5(...)),
            new TwigFilter('base64_encode', $this->base64Encode(...)),
            new TwigFilter('base64_decode', $this->base64Decode(...)),
            new TwigFilter('yaml_encode', $this->yamlEncode(...)),
            new TwigFilter('yaml_decode', $this->yamlDecode(...)),
            new TwigFilter('json_decode', $this->jsonDecode(...)),
            new TwigFilter('json_encode', $this->jsonEncode(...)),

            # Pretty
            new TwigFilter('file_pretty_size', $this->filePrettySizeFilter(...)),
            new TwigFilter('file_pretty_image_size', $this->filePrettyImageSizeFilter(...)),

            // Casts
            new TwigFilter('string', $this->stringFilter(...)),
            new TwigFilter('int', $this->intFilter(...)),
            new TwigFilter('bool', $this->boolFilter(...)),
            new TwigFilter('float', $this->floatFilter(...)),
            new TwigFilter('array', $this->arrayFilter(...)),

            // System
            new TwigFilter('basename', 'basename'),
            new TwigFilter('dirname', 'dirname'),
            new TwigFilter('print_r', 'print_r'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            # storage
            new TwigFunction('put_to_storage', $this->putToStorage(...), ['is_safe' => ['all']]),
            new TwigFunction('get_from_storage', $this->getFromStorage(...), ['is_safe' => ['all']]),

            new TwigFunction('spacer', $this->spacer(...), ['is_safe' => ['all']]),

            # System
            new TwigFunction('array_key_exists', 'array_key_exists'),
            new TwigFunction('array_unique',     'array_unique'),
            new TwigFunction('print_r',          'print_r'),
            new TwigFunction('range',            'range'),
            new TwigFunction('pathinfo',         'pathinfo'),

            new TwigFunction('is_ajax_request', $this->isAjaxRequest(...)),
            new TwigFunction('array_intersect', $this->arrayIntersect(...)),

            new TwigFunction('static_var', function ($name) {
                list($class, $property) = explode('::', $name, 2);
                if (property_exists($class, $property)) {
                    return $class::$$property;
                }
                return null;
            }),

            # Hash and decodes
            new TwigFunction('sha1', $this->sha1(...)),
            new TwigFunction('md5', $this->md5(...)),
            new TwigFunction('base64_encode', $this->base64Encode(...)),
            new TwigFunction('base64_decode', $this->base64Decode(...)),
            new TwigFunction('yaml_encode', $this->yamlEncode(...)),
            new TwigFunction('yaml_decode', $this->yamlDecode(...)),
            new TwigFunction('json_decode', $this->jsonDecode(...)),
            new TwigFunction('json_encode', $this->jsonEncode(...)),

            // Casts
            new TwigFunction('string', $this->stringFilter(...)),
            new TwigFunction('int', $this->intFilter(...)),
            new TwigFunction('bool', $this->boolFilter(...)),
            new TwigFunction('float', $this->floatFilter(...)),
            new TwigFunction('array', $this->arrayFilter(...)),
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Pure] public function getTokenParsers(): array
    {
        return [
            new NoindexTokenParser(),
            new HeadtagTokenParser(),
            new SwitchTokenParser(),
        ];
    }

    public function yamlEncode($data, $inline = 10): string
    {
        return YamlUtil::encode($data, $inline);
    }

    public function yamlDecode($data): array
    {
        return YamlUtil::decode($data);
    }

    public function noindex(string $text = ''): string
    {
        $lines = [];
        $lines[] = '<!--googleoff: all-->';
        $lines[] = '<!--noindex-->';
        $lines[] = $text;
        $lines[] = '<!--/noindex-->';
        $lines[] = '<!--googleon: all-->';
        return implode("\n", $lines);
    }

    public function putToStorage(string $var, mixed $value): void
    {
        $this->storage[$var] = $value;
    }

    public function getFromStorage($var, $default = null)
    {
        return $this->storage[$var] ?? $default;
    }

    public function stringFilter($input): string
    {
        return (string) $input;
    }

    public function intFilter($input): int
    {
        return (int) $input;
    }

    public function boolFilter($input): bool
    {
        return (bool) $input;
    }

    public function floatFilter($input): float
    {
        return (float) $input;
    }

    public function arrayFilter($input): array
    {
        return (array) $input;
    }

    public function sha1($str): string
    {
        return sha1((string)$str);
    }

    public function md5($str): string
    {
        return md5((string)$str);
    }

    public function base64Encode($str): string
    {
        return base64_encode((string)$str);
    }

    public function base64Decode($str): bool|string
    {
        return base64_decode((string)$str);
    }

    public function filePrettySizeFilter(File $file): string
    {
        $bytes = $file->getSize();

        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$unit[(int)$i];
    }

    public function filePrettyImageSizeFilter(File $file): string
    {
        if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'ico']) && is_file($file->getRealPath())) {
            $imageInfo = getimagesize($file->getRealPath());
            $type = null;
            if (isset($imageInfo[0])) {
                if (isset($imageInfo['mime']) && ($mime = $imageInfo['mime']) && str_starts_with($mime, 'image/')) {
                    $type = strtoupper(str_replace('image/', '', $mime));
                }

                if ($type) {
                    return sprintf('%s@%sx%spx', $type, $imageInfo[0], $imageInfo[1]);
                } else {
                    return sprintf('%sx%spx', $imageInfo[0], $imageInfo[1]);
                }
            }
        }

        return '';
    }

    public function spacer($width, $tag = 'div'): string
    {
        $width = (preg_match('#^\d+$#', $width)) ? $width . 'px' : $width;
        return sprintf('<%s style="width:%s"></%s>', $tag, $width, $tag);
    }

    public function isAjaxRequest(): bool
    {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    public function fieldNameFilter($str): string
    {
        $path = explode('.', rtrim($str, '.'));
        return array_shift($path) . ($path ? '[' . implode('][', $path) . ']' : '');
    }

    public function jsonDecode($str, $assoc = false, $depth = 512, $options = 0)
    {
        return json_decode(html_entity_decode($str), $assoc, $depth, $options);
    }

    public function jsonEncode(array $array): bool|string
    {
        return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function headtag(string $content = ''): string
    {
        $lines = array_map('trim', explode("\n", $content));
        $lines = array_filter($lines);

        $result = '';
        foreach ($lines as $line) {
            $result .= '    '.$line."\n";
        }

        return $result;
    }

    #[Pure] public function arrayIntersect(array|ArrayCollection $array1, array|ArrayCollection $array2): ArrayCollection|array
    {
        if ($array1 instanceof ArrayCollection && $array2 instanceof ArrayCollection) {
            return new ArrayCollection(
                array_merge($array1->toArray(), $array1->toArray())
            );
        }

        return array_intersect($array1, $array2);
    }
}
