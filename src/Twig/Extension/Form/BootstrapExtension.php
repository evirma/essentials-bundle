<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\Extension\Form;

use JetBrains\PhpStorm\Pure;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BootstrapExtension extends AbstractExtension
{
    private string $style = '';
    private string $colSize = 'lg';
    private int $widgetCol = 9;
    private int $labelCol = 3;
    private ?int $simpleCol = null;
    private array $settingsStack = [];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('bootstrap_show_global_errors', $this->showGlobalErrors(...)),
            new TwigFunction('bootstrap_set_style', $this->setStyle(...)),
            new TwigFunction('bootstrap_get_style', $this->getStyle(...)),
            new TwigFunction('bootstrap_set_col_size', $this->setColSize(...)),
            new TwigFunction('bootstrap_get_col_size', $this->getColSize(...)),
            new TwigFunction('bootstrap_set_widget_col', $this->setWidgetCol(...)),
            new TwigFunction('bootstrap_get_widget_col', $this->getWidgetCol(...)),
            new TwigFunction('bootstrap_set_label_col', $this->setLabelCol(...)),
            new TwigFunction('bootstrap_get_label_col', $this->getLabelCol(...)),
            new TwigFunction('bootstrap_set_simple_col', $this->setSimpleCol(...)),
            new TwigFunction('bootstrap_get_simple_col', $this->getSimpleCol(...)),
            new TwigFunction('bootstrap_backup_form_settings', $this->backupFormSettings(...)),
            new TwigFunction('bootstrap_restore_form_settings', $this->restoreFormSettings(...)),
            new TwigFunction('checkbox_row',null, array('is_safe' => array('html'), 'node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode')),
            new TwigFunction('radio_row', null, array('is_safe' => array('html'), 'node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode')),
            new TwigFunction(
                'global_form_errors',
                null,
                array('is_safe' => array('html'), 'node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode')
            ),
            new TwigFunction(
                'form_control_static',
                $this->formControlStaticFunction(...),
                array('is_safe' => array('html'))
            ),
        ];
    }

    public function getName(): string
    {
        return 'bootstrap_form';
    }

    public function showGlobalErrors(FormView $formView): string
    {
        if (!$errors = $this->collectFormViewErrors($formView)) {
            return '';
        }

        $html = '<div class="alert alert-danger">';
        $html .= implode('<br/>', $errors);
        $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
        $html .= '</div>';

        return $html;
    }

    private function collectFormViewErrors(FormView $formView): array
    {
        $result = [];
        if (!$formView->vars['valid']) {
            /** @var FormError $error */
            foreach ($formView->vars['errors'] as $error) {
                $result[] = $error->getMessage();
            }

            $result = array_merge($result, $this->collectFormViewChildrenErrors($formView));
        }

        return $result;
    }

    private function collectFormViewChildrenErrors(FormView $formView): array
    {
        $result = [];
        if (!empty($formView->children)) {
            foreach ($formView->children as $child) {
                if (!$child->vars['valid']) {
                    /** @var FormError $error */
                    foreach ($child->vars['errors'] as $error) {
                        $id = $child->vars['id'];
                        $message = '';
                        if ($child->vars['label']) {
                            $message .= '<b>'.$child->vars['label'].'</b>: ';
                        }
                        $message .= $error->getMessage();
                        $result[] = sprintf(/** @lang text */ '<label style="cursor: pointer" for="%s">%s</label>', $id, $message);
                    }
                }
                if (!empty($child->children)) {
                    foreach ($child->children as $ch) {
                        $result = array_merge($result, $this->collectFormViewChildrenErrors($ch));
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Sets the style.
     *
     * @param string $style Name of the style
     */
    public function setStyle(string $style): void
    {
        $this->style = $style;
    }

    /**
     * Returns the style.
     *
     * @return string Name of the style
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * Sets the column size.
     *
     * @param string $colSize Column size (xs, sm, md or lg)
     */
    public function setColSize(string $colSize): void
    {
        $this->colSize = $colSize;
    }

    /**
     * Returns the column size.
     *
     * @return string Column size (xs, sm, md or lg)
     */
    public function getColSize(): string
    {
        return $this->colSize;
    }

    /**
     * Sets the number of columns of widgets.
     *
     * @param integer $widgetCol Number of columns.
     */
    public function setWidgetCol(int $widgetCol): void
    {
        $this->widgetCol = $widgetCol;
    }

    /**
     * Returns the number of columns of widgets.
     *
     * @return integer Number of columns.Class
     */
    public function getWidgetCol(): int
    {
        return $this->widgetCol;
    }

    /**
     * Sets the number of columns of labels.
     *
     * @param integer $labelCol Number of columns.
     */
    public function setLabelCol(int $labelCol): void
    {
        $this->labelCol = $labelCol;
    }

    /**
     * Returns the number of columns of labels.
     *
     * @return integer Number of columns.
     */
    public function getLabelCol(): int
    {
        return $this->labelCol;
    }

    public function setSimpleCol(int $simpleCol): void
    {
        $this->simpleCol = $simpleCol;
    }

    public function getSimpleCol(): ?int
    {
        return $this->simpleCol;
    }

    /**
     * Backup the form settings to the stack.
     *
     * @internal Should only be used at the beginning of form_start. This allows
     *           a nested subform to change its settings without affecting its
     *           parent form.
     */
    public function backupFormSettings(): void
    {
        $settings = [
            'style'     => $this->style,
            'colSize'   => $this->colSize,
            'widgetCol' => $this->widgetCol,
            'labelCol'  => $this->labelCol,
            'simpleCol' => $this->simpleCol,
        ];

        $this->settingsStack[] = $settings;
    }

    /**
     * Restore the form settings from the stack.
     *
     * @internal Should only be used at the end of form_end.
     * @see backupFormSettings
     */
    public function restoreFormSettings(): void
    {
        if (count($this->settingsStack) < 1) {
            return;
        }

        $settings = array_pop($this->settingsStack);

        $this->style     = $settings['style'];
        $this->colSize   = $settings['colSize'];
        $this->widgetCol = $settings['widgetCol'];
        $this->labelCol  = $settings['labelCol'];
        $this->simpleCol = $settings['simpleCol'];
    }

    /**
     * @param string $label
     * @param string $value
     * @return string
     */
    #[Pure] public function formControlStaticFunction(string $label, string $value): string
    {
        return  sprintf(
            '<div class="form-group"><label class="col-sm-%s control-label">%s</label><div class="col-sm-%s"><p class="form-control-static">%s</p></div></div>',
            $this->getLabelCol(), $label, $this->getWidgetCol(), $value
        );
    }
}
