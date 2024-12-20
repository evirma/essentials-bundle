<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

class SwitchNode extends Node
{
    /**
     * TwigNodeSwitch constructor.
     *
     * @param Node        $value
     * @param Node        $cases
     * @param Node|null   $default
     * @param int         $lineno
     * @param string|null $tag
     */
    public function __construct(
        Node $value,
        Node $cases,
        ?Node $default = null,
        $lineno = 0,
        ?string $tag = null
    )
    {
        parent::__construct(array('value' => $value, 'cases' => $cases, 'default' => $default), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler $compiler A Twig_Compiler instance
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('switch (')
            ->subcompile($this->getNode('value'))
            ->raw(") {\n")
            ->indent();

        foreach ($this->getNode('cases') as $case) {
            if (!$case->hasNode('body')) {
                continue;
            }

            foreach ($case->getNode('values') as $value) {
                $compiler
                    ->write('case ')
                    ->subcompile($value)
                    ->raw(":\n");
            }

            $compiler
                ->write("{\n")
                ->indent()
                ->subcompile($case->getNode('body'))
                ->write("break;\n")
                ->outdent()
                ->write("}\n");
        }

        if ($this->hasNode('default') && $this->getNode('default') !== null) {
            $compiler
                ->write("default:\n")
                ->write("{\n")
                ->indent()
                ->subcompile($this->getNode('default'))
                ->outdent()
                ->write("}\n");
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }
}
