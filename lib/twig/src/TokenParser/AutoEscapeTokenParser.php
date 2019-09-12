<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\TokenParser;

use WCML\Twig\Error\SyntaxError;
use WCML\Twig\Node\AutoEscapeNode;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Token;
/**
 * Marks a section of a template to be escaped or not.
 *
 *   {% autoescape true %}
 *     Everything will be automatically escaped in this block
 *   {% endautoescape %}
 *
 *   {% autoescape false %}
 *     Everything will be outputed as is in this block
 *   {% endautoescape %}
 *
 *   {% autoescape true js %}
 *     Everything will be automatically escaped in this block
 *     using the js escaping strategy
 *   {% endautoescape %}
 *
 * @final
 */
class AutoEscapeTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        if ($stream->test(\WCML\Twig\Token::BLOCK_END_TYPE)) {
            $value = 'html';
        } else {
            $expr = $this->parser->getExpressionParser()->parseExpression();
            if (!$expr instanceof \WCML\Twig\Node\Expression\ConstantExpression) {
                throw new \WCML\Twig\Error\SyntaxError('An escaping strategy must be a string or a bool.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
            $value = $expr->getAttribute('value');
            $compat = \true === $value || \false === $value;
            if (\true === $value) {
                $value = 'html';
            }
            if ($compat && $stream->test(\WCML\Twig\Token::NAME_TYPE)) {
                @\trigger_error('Using the autoescape tag with "true" or "false" before the strategy name is deprecated since version 1.21.', \E_USER_DEPRECATED);
                if (\false === $value) {
                    throw new \WCML\Twig\Error\SyntaxError('Unexpected escaping strategy as you set autoescaping to false.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
                }
                $value = $stream->next()->getValue();
            }
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return new \WCML\Twig\Node\AutoEscapeNode($value, $body, $lineno, $this->getTag());
    }
    public function decideBlockEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endautoescape');
    }
    public function getTag()
    {
        return 'autoescape';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\AutoEscapeTokenParser', 'WCML\\Twig_TokenParser_AutoEscape');
