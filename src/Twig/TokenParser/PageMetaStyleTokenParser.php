<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\TokenParser;

use JetBrains\PhpStorm\Pure;
use Twig\Token;
use Evirma\Bundle\EssentialsBundle\Twig\Node\PageMetaStorageNode;

class PageMetaStyleTokenParser extends PageMetaStorageTokenParser
{
    protected $nodeClass = PageMetaStorageNode::class;
    protected $groupPrefix = 'style_';

    #[Pure] public function decideMarkdownEnd(Token $token)
    {
        return $token->test('endstyle');
    }

    public function getTag()
    {
        return 'style';
    }
}