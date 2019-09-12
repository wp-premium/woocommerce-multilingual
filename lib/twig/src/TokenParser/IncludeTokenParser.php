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

use WCML\Twig\Node\IncludeNode;
use WCML\Twig\Token;
/**
 * Includes a template.
 *
 *   {% include 'header.html' %}
 *     Body
 *   {% include 'footer.html' %}
 */
class IncludeTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        list($variables, $only, $ignoreMissing) = $this->parseArguments();
        return new \WCML\Twig\Node\IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }
    protected function parseArguments()
    {
        $stream = $this->parser->getStream();
        $ignoreMissing = \false;
        if ($stream->nextIf(\WCML\Twig\Token::NAME_TYPE, 'ignore')) {
            $stream->expect(\WCML\Twig\Token::NAME_TYPE, 'missing');
            $ignoreMissing = \true;
        }
        $variables = null;
        if ($stream->nextIf(\WCML\Twig\Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }
        $only = \false;
        if ($stream->nextIf(\WCML\Twig\Token::NAME_TYPE, 'only')) {
            $only = \true;
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return [$variables, $only, $ignoreMissing];
    }
    public function getTag()
    {
        return 'include';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\IncludeTokenParser', 'WCML\\Twig_TokenParser_Include');
