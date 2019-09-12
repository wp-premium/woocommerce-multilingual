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

use WCML\Twig\Node\SpacelessNode;
use WCML\Twig\Token;
/**
 * Remove whitespaces between HTML tags.
 *
 *   {% spaceless %}
 *      <div>
 *          <strong>foo</strong>
 *      </div>
 *   {% endspaceless %}
 *   {# output will be <div><strong>foo</strong></div> #}
 *
 * @final
 */
class SpacelessTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], \true);
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return new \WCML\Twig\Node\SpacelessNode($body, $lineno, $this->getTag());
    }
    public function decideSpacelessEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endspaceless');
    }
    public function getTag()
    {
        return 'spaceless';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\SpacelessTokenParser', 'WCML\\Twig_TokenParser_Spaceless');
