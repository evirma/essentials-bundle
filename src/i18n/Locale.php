<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\i18n;

enum Locale: string {
    case RU = 'ru';
    case EN = 'en';
    case DE = 'de';

    public function isRu(): bool
    {
        return $this === Locale::RU;
    }

    public function isEn(): bool
    {
        return $this === Locale::EN;
    }

}