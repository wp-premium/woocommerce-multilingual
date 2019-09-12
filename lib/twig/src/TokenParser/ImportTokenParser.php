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

use WCML\Twig\Node\Expression\AssignNameExpression;
use WCML\Twig\Node\ImportNode;
use WCML\Twig\Token;
/**
 * Imports macros.
 *
 *   {% import 'forms.html' as forms %}
 *
 * @final
 */
class ImportTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(\WCML\Twig\Token::NAME_TYPE, 'as');
        $var = new \WCML\Twig\Node\Expression\AssignNameExpression($this->parser->getStream()->expect(\WCML\Twig\Token::NAME_TYPE)->getValue(), $token->getLine());
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $this->parser->addImportedSymbol('template', $var->getAttribute('name'));
        return new \WCML\Twig\Node\ImportNode($macro, $var, $token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'import';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\ImportTokenParser', 'WCML\\Twig_TokenParser_Import');
