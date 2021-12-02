<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\TokenParser;

use Evirma\Bundle\EssentialsBundle\Twig\Node\PageMetaStorageNode;
use JetBrains\PhpStorm\Pure;
use Twig\Token;

class PageMetaJavascriptTokenParser extends PageMetaStorageTokenParser
{
    protected $nodeClass = PageMetaStorageNode::class;
    protected $groupPrefix = 'javascript_';

    #[Pure] public function decideMarkdownEnd(Token $token)
    {
        return $token->test('endjavascript');
    }

    public function getTag()
    {
        return 'javascript';
    }
}