<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\Extension;

use DateTime;
use Evirma\Bundle\EssentialsBundle\Util\DateUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_html_format', $this->dateHtmlFormatFilter(...), ['is_safe' => ['all']]),
            new TwigFilter('date_html_pretty', $this->dateHtmlPrettyFilter(...), ['is_safe' => ['all']]),
            new TwigFilter('diff_pretty_time', $this->diffPrettyTimeFilter(...)),
        ];
    }

    public function dateHtmlFormatFilter($date, $format = 'd.m.Y H:i', $withTimeTag = false): string
    {
        $oldLocal = setlocale(LC_TIME, 'en', 'en_EN', 'en_EN.UTF-8');

        if (is_object($date) && is_a($date, 'DateTime')) {
            /* @var $date DateTime */
            $date = $date->getTimestamp();
        } elseif (!is_numeric($date)) {
            $date = strtotime($date);
        }

        $result = date($format, $date);
        $result = DateUtil::dateReplace($result);

        setlocale(LC_TIME, $oldLocal);

        if ($withTimeTag) {
            return sprintf('<time datetime="%s">%s</time>', date('c', $date), $result);
        } else {
            return $result;
        }
    }

    public function dateHtmlPrettyFilter($date, $withTimeTag = false, $withTitleFormat = null): string
    {
        $oldLocal = setlocale(LC_TIME, 'en', 'en_EN', 'en_EN.UTF-8');


        if (is_object($date) && is_a($date, 'DateTime')) {
            /* @var $date DateTime */
            $date = $date->getTimestamp();
        } elseif (!is_numeric($date)) {
            $date = strtotime($date);
        }

        if ((date('Y') != date('Y', $date))) { // Другой год
            $result = date('j F Y, H:i', $date);
        } elseif (date('Ymd') == date('Ymd', $date)) {
            $result = 'Today, ' . date('H:i', $date);
        } elseif (date('Ymd', time() - 86400) == date('Ymd', $date)) {
            $result = 'Yesterday, ' . date('H:i', $date);
        } else {
            $result = date("j F, H:i", $date);
        }

        $result = str_replace(', 00:00', '', $result);
        $result = DateUtil::dateReplace($result);
        setlocale(LC_TIME, $oldLocal);

        if ($withTitleFormat === true) {
            $withTitleFormat = 'Y-m-d H:i:s';
        }

        $title = false;
        if ($withTitleFormat) {
            $title = date($withTitleFormat, $date);
        }

        if ($withTimeTag) {
            if ($title) {
                return sprintf('<time datetime="%s" title="%s">%s</time>', date('c', $date), $title, $result);
            }
            return sprintf('<time datetime="%s">%s</time>', date('c', $date), $result);
        } else {
            if ($title) {
                return sprintf('<span title="%s">%s</span>', $title, $result);
            }
            return $result;
        }
    }

    public function diffPrettyTimeFilter($date): string
    {
        if (is_object($date) && is_a($date, 'DateTime')) {
            /* @var $date DateTime */
            $date = $date->getTimestamp();
        } elseif (!is_numeric($date)) {
            $date = strtotime($date);
        }

        $dateDiff = (new DateTime())->setTimestamp($date)->diff(new DateTime('now'));

        $result = "";
        if ($dateDiff->y != 0) {
            $result .= $dateDiff->y.'г ';
        }

        if ($dateDiff->m != 0) {
            $result .= $dateDiff->m.'м ';
        }

        if ($dateDiff->d != 0) {
            $result .= $dateDiff->d.'д ';
        }

        if ($dateDiff->h != 0) {
            $result .= $dateDiff->h.'ч ';
        }

        if ($dateDiff->i != 0) {
            $result .= $dateDiff->i.'м ';
        }

        return trim($result);
    }
}
