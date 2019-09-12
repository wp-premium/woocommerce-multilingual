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
use WCML\Twig\Node\BlockNode;
use WCML\Twig\Node\BlockReferenceNode;
use WCML\Twig\Node\Node;
use WCML\Twig\Node\PrintNode;
use WCML\Twig\Token;
/**
 * Marks a section of a template as being reusable.
 *
 *  {% block head %}
 *    <link rel="stylesheet" href="style.css" />
 *    <title>{% block title %}{% endblock %} - My Webpage</title>
 *  {% endblock %}
 *
 * @final
 */
class BlockTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(\WCML\Twig\Token::NAME_TYPE)->getValue();
        if ($this->parser->hasBlock($name)) {
            throw new \WCML\Twig\Error\SyntaxError(\sprintf("The block '%s' has already been defined line %d.", $name, $this->parser->getBlock($name)->getTemplateLine()), $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        $this->parser->setBlock($name, $block = new \WCML\Twig\Node\BlockNode($name, new \WCML\Twig\Node\Node([]), $lineno));
        $this->parser->pushLocalScope();
        $this->parser->pushBlockStack($name);
        if ($stream->nextIf(\WCML\Twig\Token::BLOCK_END_TYPE)) {
            $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
            if ($token = $stream->nextIf(\WCML\Twig\Token::NAME_TYPE)) {
                $value = $token->getValue();
                if ($value != $name) {
                    throw new \WCML\Twig\Error\SyntaxError(\sprintf('Expected endblock for block "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
                }
            }
        } else {
            $body = new \WCML\Twig\Node\Node([new \WCML\Twig\Node\PrintNode($this->parser->getExpressionParser()->parseExpression(), $lineno)]);
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $block->setNode('body', $body);
        $this->parser->popBlockStack();
        $this->parser->popLocalScope();
        return new \WCML\Twig\Node\BlockReferenceNode($name, $lineno, $this->getTag());
    }
    public function decideBlockEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endblock');
    }
    public function getTag()
    {
        return 'block';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\BlockTokenParser', 'WCML\\Twig_TokenParser_Block');
