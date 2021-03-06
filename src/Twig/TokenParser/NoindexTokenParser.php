<?php

namespace Evirma\Bundle\EssentialsBundle\Twig\TokenParser;

use Evirma\Bundle\EssentialsBundle\Twig\Node\NoindexNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class NoindexTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): NoindexNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $this->parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $parser->subparse(array($this, 'decideMarkdownEnd'), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new NoindexNode($body, $lineno, $this->getTag());
    }

    /** @noinspection PhpPureAttributeCanBeAddedInspection */
    public function decideMarkdownEnd(Token $token): bool
    {
        return $token->test('endnoindex');
    }

    public function getTag(): string
    {
        return 'noindex';
    }
}