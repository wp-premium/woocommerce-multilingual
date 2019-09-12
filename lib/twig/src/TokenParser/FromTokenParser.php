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
use WCML\Twig\Node\Expression\AssignNameExpression;
use WCML\Twig\Node\ImportNode;
use WCML\Twig\Token;
/**
 * Imports macros.
 *
 *   {% from 'forms.html' import forms %}
 *
 * @final
 */
class FromTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(\WCML\Twig\Token::NAME_TYPE, 'import');
        $targets = [];
        do {
            $name = $stream->expect(\WCML\Twig\Token::NAME_TYPE)->getValue();
            $alias = $name;
            if ($stream->nextIf('as')) {
                $alias = $stream->expect(\WCML\Twig\Token::NAME_TYPE)->getValue();
            }
            $targets[$name] = $alias;
            if (!$stream->nextIf(\WCML\Twig\Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        } while (\true);
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $var = new \WCML\Twig\Node\Expression\AssignNameExpression($this->parser->getVarName(), $token->getLine());
        $node = new \WCML\Twig\Node\ImportNode($macro, $var, $token->getLine(), $this->getTag());
        foreach ($targets as $name => $alias) {
            if ($this->parser->isReservedMacroName($name)) {
                throw new \WCML\Twig\Error\SyntaxError(\sprintf('"%s" cannot be an imported macro as it is a reserved keyword.', $name), $token->getLine(), $stream->getSourceContext());
            }
            $this->parser->addImportedSymbol('function', $alias, 'get' . $name, $var);
        }
        return $node;
    }
    public function getTag()
    {
        return 'from';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\FromTokenParser', 'WCML\\Twig_TokenParser_From');
