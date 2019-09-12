<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\TokenParser;

use WCML\Twig\Error\SyntaxError;
use WCML\Twig\Node\IfNode;
use WCML\Twig\Node\Node;
use WCML\Twig\Token;
/**
 * Tests a condition.
 *
 *   {% if users %}
 *    <ul>
 *      {% for user in users %}
 *        <li>{{ user.username|e }}</li>
 *      {% endfor %}
 *    </ul>
 *   {% endif %}
 *
 * @final
 */
class IfTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideIfFork']);
        $tests = [$expr, $body];
        $else = null;
        $end = \false;
        while (!$end) {
            switch ($stream->next()->getValue()) {
                case 'else':
                    $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
                    $else = $this->parser->subparse([$this, 'decideIfEnd']);
                    break;
                case 'elseif':
                    $expr = $this->parser->getExpressionParser()->parseExpression();
                    $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse([$this, 'decideIfFork']);
                    $tests[] = $expr;
                    $tests[] = $body;
                    break;
                case 'endif':
                    $end = \true;
                    break;
                default:
                    throw new \WCML\Twig\Error\SyntaxError(\sprintf('Unexpected end of template. Twig was looking for the following tags "else", "elseif", or "endif" to close the "if" block started at line %d).', $lineno), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return new \WCML\Twig\Node\IfNode(new \WCML\Twig\Node\Node($tests), $else, $lineno, $this->getTag());
    }
    public function decideIfFork(\WCML\Twig\Token $token)
    {
        return $token->test(['elseif', 'else', 'endif']);
    }
    public function decideIfEnd(\WCML\Twig\Token $token)
    {
        return $token->test(['endif']);
    }
    public function getTag()
    {
        return 'if';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\IfTokenParser', 'WCML\\Twig_TokenParser_If');
