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

use WCML\Twig\Node\Expression\TempNameExpression;
use WCML\Twig\Node\Node;
use WCML\Twig\Node\PrintNode;
use WCML\Twig\Node\SetNode;
use WCML\Twig\Token;
/**
 * Applies filters on a section of a template.
 *
 *   {% apply upper %}
 *      This text becomes uppercase
 *   {% endapplys %}
 */
final class ApplyTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $name = $this->parser->getVarName();
        $ref = new \WCML\Twig\Node\Expression\TempNameExpression($name, $lineno);
        $ref->setAttribute('always_defined', \true);
        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideApplyEnd'], \true);
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return new \WCML\Twig\Node\Node([new \WCML\Twig\Node\SetNode(\true, $ref, $body, $lineno, $this->getTag()), new \WCML\Twig\Node\PrintNode($filter, $lineno, $this->getTag())]);
    }
    public function decideApplyEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endapply');
    }
    public function getTag()
    {
        return 'apply';
    }
}
