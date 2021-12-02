<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\TypeExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TabExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['tab']);
        $resolver->setDefaults([
            'tab' => [
                'namespace' => null,
                'name' => null,
                'label' => null,
                'pos' => 99
            ]
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $namespace = $options['tab']['namespace'];
        if (null === $namespace) {
            return;
        }

        $tabName = $options['tab']['name'] ?? $namespace;
        $tabLabel = $options['tab']['label'] ?? $namespace;
        $tabPos = $options['tab']['pos'] ?? 99;

        $root = $this->getRootView($view);
        if (!isset($root->vars['tabs'][$namespace][$tabName])) {
            $root->vars['tabs'][$namespace][$tabName] =
                [
                    'name' => $tabName,
                    'label' => $tabLabel,
                    'pos'   => $tabPos,
                ];
        }

        $item = [
            'name' => $form->getName(),
            'pos' => $view->vars['attr']['pos'] ?? 99
        ];

        if (!isset($root->vars['tabs'][$namespace][$tabName]['elements'])) {
            $root->vars['tabs'][$namespace][$tabName]['elements'] = [$item];
        } else {
            $root->vars['tabs'][$namespace][$tabName]['elements'][] = $item;
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $root = $this->getRootView($view);
        if (isset($root->vars['tabs'])) {
            foreach ($root->vars['tabs'] as &$tabs) {
                if (count($tabs) > 1) {
                    uasort(
                        $tabs,
                        function ($a, $b) {
                            return $a['pos'] <=> $b['pos'];
                        }
                    );
                }

                foreach ($tabs as &$tab) {
                    uasort($tab['elements'], function ($a, $b) {
                        return $a['pos'] <=> $b['pos'];
                    });
                }
            }
        }

        parent::finishView($view, $form, $options);
    }

    public function getRootView(FormView $view): ?FormView
    {
        $root = $view->parent;
        while (null !== $root) {
            if (is_null($root->parent)) {
                break;
            }
            $root = $root->parent;
        }

        return $root;
    }
}