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
use WCML\Twig\Node\Expression\AssignNameExpression;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\Expression\GetAttrExpression;
use WCML\Twig\Node\Expression\NameExpression;
use WCML\Twig\Node\ForNode;
use WCML\Twig\Token;
use WCML\Twig\TokenStream;
/**
 * Loops over each item of a sequence.
 *
 *   <ul>
 *    {% for user in users %}
 *      <li>{{ user.username|e }}</li>
 *    {% endfor %}
 *   </ul>
 *
 * @final
 */
class ForTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $stream->expect(\WCML\Twig\Token::OPERATOR_TYPE, 'in');
        $seq = $this->parser->getExpressionParser()->parseExpression();
        $ifexpr = null;
        if ($stream->nextIf(\WCML\Twig\Token::NAME_TYPE, 'if')) {
            $ifexpr = $this->parser->getExpressionParser()->parseExpression();
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideForFork']);
        if ('else' == $stream->next()->getValue()) {
            $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
            $else = $this->parser->subparse([$this, 'decideForEnd'], \true);
        } else {
            $else = null;
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        if (\count($targets) > 1) {
            $keyTarget = $targets->getNode(0);
            $keyTarget = new \WCML\Twig\Node\Expression\AssignNameExpression($keyTarget->getAttribute('name'), $keyTarget->getTemplateLine());
            $valueTarget = $targets->getNode(1);
            $valueTarget = new \WCML\Twig\Node\Expression\AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        } else {
            $keyTarget = new \WCML\Twig\Node\Expression\AssignNameExpression('_key', $lineno);
            $valueTarget = $targets->getNode(0);
            $valueTarget = new \WCML\Twig\Node\Expression\AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        }
        if ($ifexpr) {
            $this->checkLoopUsageCondition($stream, $ifexpr);
            $this->checkLoopUsageBody($stream, $body);
        }
        return new \WCML\Twig\Node\ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, $lineno, $this->getTag());
    }
    public function decideForFork(\WCML\Twig\Token $token)
    {
        return $token->test(['else', 'endfor']);
    }
    public function decideForEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endfor');
    }
    // the loop variable cannot be used in the condition
    protected function checkLoopUsageCondition(\WCML\Twig\TokenStream $stream, \WCML\Twig_NodeInterface $node)
    {
        if ($node instanceof \WCML\Twig\Node\Expression\GetAttrExpression && $node->getNode('node') instanceof \WCML\Twig\Node\Expression\NameExpression && 'loop' == $node->getNode('node')->getAttribute('name')) {
            throw new \WCML\Twig\Error\SyntaxError('The "loop" variable cannot be used in a looping condition.', $node->getTemplateLine(), $stream->getSourceContext());
        }
        foreach ($node as $n) {
            if (!$n) {
                continue;
            }
            $this->checkLoopUsageCondition($stream, $n);
        }
    }
    // check usage of non-defined loop-items
    // it does not catch all problems (for instance when a for is included into another or when the variable is used in an include)
    protected function checkLoopUsageBody(\WCML\Twig\TokenStream $stream, \WCML\Twig_NodeInterface $node)
    {
        if ($node instanceof \WCML\Twig\Node\Expression\GetAttrExpression && $node->getNode('node') instanceof \WCML\Twig\Node\Expression\NameExpression && 'loop' == $node->getNode('node')->getAttribute('name')) {
            $attribute = $node->getNode('attribute');
            if ($attribute instanceof \WCML\Twig\Node\Expression\ConstantExpression && \in_array($attribute->getAttribute('value'), ['length', 'revindex0', 'revindex', 'last'])) {
                throw new \WCML\Twig\Error\SyntaxError(\sprintf('The "loop.%s" variable is not defined when looping with a condition.', $attribute->getAttribute('value')), $node->getTemplateLine(), $stream->getSourceContext());
            }
        }
        // should check for parent.loop.XXX usage
        if ($node instanceof \WCML\Twig\Node\ForNode) {
            return;
        }
        foreach ($node as $n) {
            if (!$n) {
                continue;
            }
            $this->checkLoopUsageBody($stream, $n);
        }
    }
    public function getTag()
    {
        return 'for';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\ForTokenParser', 'WCML\\Twig_TokenParser_For');
