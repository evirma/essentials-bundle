<?php /** @noinspection PhpPureAttributeCanBeAddedInspection */

namespace Evirma\Bundle\EssentialsBundle\Twig\TokenParser;

use Evirma\Bundle\EssentialsBundle\Twig\Node\SwitchNode;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Adds ability use elegant switch instead of ungainly if statements
 *
 * {% switch type %}
 *   {% case 'foo' %}
 *      {{ my_data.foo }}
 *   {% case 'bar' %}
 *      {{ my_data.bar }}
 *   {% default %}
 *      {{ my_data.default }}
 * {% endswitch %}
 */
class SwitchTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $name = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        // There can be some whitespace between the {% switch %} and first {% case %} tag.
        while ($stream->getCurrent()->getType() === Token::TEXT_TYPE && trim($stream->getCurrent()->getValue()) === '') {
            $stream->next();
        }

        $stream->expect(Token::BLOCK_START_TYPE);

        $expressionParser = $this->parser->getExpressionParser();

        $default = null;
        $cases = [];
        $end = false;

        while (!$end) {
            $next = $stream->next();

            switch ($next->getValue()) {
                case 'case':
                    $values = [];

                    while (true) {
                        $values[] = $expressionParser->parsePrimaryExpression();
                        // Multiple allowed values?
                        if ($stream->test(Token::OPERATOR_TYPE, 'or')) {
                            $stream->next();
                        } else {
                            break;
                        }
                    }

                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse(array($this, 'decideIfFork'));
                    $cases[] = new Node([
                        'values' => new Node($values),
                        'body' => $body
                    ]);
                    break;

                case 'default':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $default = $this->parser->subparse(array($this, 'decideIfEnd'));
                    break;

                case 'endswitch':
                    $end = true;
                    break;

                default:
                    throw new SyntaxError(sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $lineno), -1);
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new SwitchNode($name, new Node($cases), $default, $lineno, $this->getTag());
    }

    public function decideIfFork(Token $token): bool
    {
        return $token->test(array('case', 'default', 'endswitch'));
    }

    public function decideIfEnd(Token $token): bool
    {
        return $token->test(array('endswitch'));
    }

    public function getTag(): string
    {
        return 'switch';
    }
}
