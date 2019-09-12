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
use WCML\Twig\Node\BodyNode;
use WCML\Twig\Node\MacroNode;
use WCML\Twig\Node\Node;
use WCML\Twig\Token;
/**
 * Defines a macro.
 *
 *   {% macro input(name, value, type, size) %}
 *      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
 *   {% endmacro %}
 *
 * @final
 */
class MacroTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(\WCML\Twig\Token::NAME_TYPE)->getValue();
        $arguments = $this->parser->getExpressionParser()->parseArguments(\true, \true);
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $this->parser->pushLocalScope();
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        if ($token = $stream->nextIf(\WCML\Twig\Token::NAME_TYPE)) {
            $value = $token->getValue();
            if ($value != $name) {
                throw new \WCML\Twig\Error\SyntaxError(\sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $this->parser->popLocalScope();
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $this->parser->setMacro($name, new \WCML\Twig\Node\MacroNode($name, new \WCML\Twig\Node\BodyNode([$body]), $arguments, $lineno, $this->getTag()));
        return new \WCML\Twig\Node\Node();
    }
    public function decideBlockEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endmacro');
    }
    public function getTag()
    {
        return 'macro';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\MacroTokenParser', 'WCML\\Twig_TokenParser_Macro');
