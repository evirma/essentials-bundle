<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\TokenParser;

use Evirma\Bundle\EssentialsBundle\Twig\Node\HeadtagNode;
use JetBrains\PhpStorm\Pure;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Adds ability to inline markdown between tags.
 * {% headtag %}
 * This is **bold** and this _underlined_
 * 1. This is a bullet list
 * 2. This is another item in that same list
 * {% endheadtag %}
 */
class HeadtagTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): HeadtagNode
    {
        $lineno = $token->getLine();
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideHeadtagEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new HeadtagNode($body, $lineno, $this->getTag());
    }

    /**
     * Decide if current token marks end of Markdown block.
     *
     * @param Token $token
     * @return bool
     */
    #[Pure] public function decideHeadtagEnd(Token $token): bool
    {
        return $token->test('endheadtag');
    }

    public function getTag(): string
    {
        return 'headtag';
    }
}
