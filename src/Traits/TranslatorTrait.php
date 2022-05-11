<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatorTrait
{
    private TranslatorInterface $translator;

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    protected function plural($one, $two, $ten, $count): string
    {
        return $this->translator->trans("$one|$two|$ten", ['%count%' => $count]);
    }

    protected function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}
