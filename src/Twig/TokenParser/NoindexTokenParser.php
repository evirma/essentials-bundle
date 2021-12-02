<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\TokenParser;

use Evirma\Bundle\EssentialsBundle\Twig\Node\NoindexNode;
use JetBrains\PhpStorm\Pure;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class NoindexTokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $this->parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $parser->subparse(array($this, 'decideMarkdownEnd'), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new NoindexNode($body, $lineno, $this->getTag());
    }

    #[Pure] public function decideMarkdownEnd(Token $token)
    {
        return $token->test('endnoindex');
    }

    public function getTag()
    {
        return 'noindex';
    }
}