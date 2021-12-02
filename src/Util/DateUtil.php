<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Util;

use DateTimeImmutable;
use DateTimeZone;
use Evirma\Bundle\EssentialsBundle\Locale\Locale;
use Exception;
use InvalidArgumentException;

class DateUtil
{
    private static array $dateReplaces = [
        'ru' => [
            'Today' => 'Сегодня',
            'Yesterday' => 'Вчера',
            'January' => 'Января',
            'February' => 'Февраля',
            'March' => 'Марта',
            'April' => 'Апреля',
            'May' => 'Мая',
            'June' => 'Июня',
            'July' => 'Июля',
            'August' => 'Августа',
            'September' => 'Сентября',
            'October' => 'Остября',
            'November' => 'Ноября',
            'December' => 'Декабря',
            'Jan' => 'Янв',
            'Feb' => 'Фев',
            'Mar' => 'Мар',
            'Apr' => 'Апр',
            'Jun' => 'Июн',
            'Jul' => 'Июл',
            'Aug' => 'Авг',
            'Sep' => 'Сен',
            'Oct' => 'Ост',
            'Nov' => 'Ноя',
            'Dec' => 'Дек',
            'Monday' => 'Понедельник',
            'Tuesday' => 'Вторник',
            'Wednesday' => 'Среда',
            'Thursday' => 'Четверт',
            'Friday' => 'Пятница',
            'Saturday' => 'Суббота',
            'Sunday' => 'Воскресенье',
            'Mon' => 'Пон',
            'Tue' => 'Вто',
            'Wed' => 'Сре',
            'Thu' => 'Чет',
            'Fri' => 'Пят',
            'Sat' => 'Суб',
            'Sun' => 'Вос',
        ]
    ];

    public static function dateReplace(string $date, Locale $locale = Locale::RU): string
    {
        if (isset(self::$dateReplaces[$locale->value])) {
            $date = strtr($date, self::$dateReplaces[$locale->value]);
        }

        return $date;
    }

    public static function fromString(string $time='now', DateTimeZone $timezone=null): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($time, $timezone);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}
