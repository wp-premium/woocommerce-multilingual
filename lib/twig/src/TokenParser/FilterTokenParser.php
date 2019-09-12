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

use WCML\Twig\Node\BlockNode;
use WCML\Twig\Node\Expression\BlockReferenceExpression;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\PrintNode;
use WCML\Twig\Token;
/**
 * Filters a section of a template by applying filters.
 *
 *   {% filter upper %}
 *      This text becomes uppercase
 *   {% endfilter %}
 *
 * @final
 */
class FilterTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $name = $this->parser->getVarName();
        $ref = new \WCML\Twig\Node\Expression\BlockReferenceExpression(new \WCML\Twig\Node\Expression\ConstantExpression($name, $token->getLine()), null, $token->getLine(), $this->getTag());
        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $block = new \WCML\Twig\Node\BlockNode($name, $body, $token->getLine());
        $this->parser->setBlock($name, $block);
        return new \WCML\Twig\Node\PrintNode($filter, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endfilter');
    }
    public function getTag()
    {
        return 'filter';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\FilterTokenParser', 'WCML\\Twig_TokenParser_Filter');
