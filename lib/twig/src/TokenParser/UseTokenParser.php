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
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\Node;
use WCML\Twig\Token;
/**
 * Imports blocks defined in another template into the current template.
 *
 *    {% extends "base.html" %}
 *
 *    {% use "blocks.html" %}
 *
 *    {% block title %}{% endblock %}
 *    {% block content %}{% endblock %}
 *
 * @see https://twig.symfony.com/doc/templates.html#horizontal-reuse for details.
 *
 * @final
 */
class UseTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $template = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        if (!$template instanceof \WCML\Twig\Node\Expression\ConstantExpression) {
            throw new \WCML\Twig\Error\SyntaxError('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        $targets = [];
        if ($stream->nextIf('with')) {
            do {
                $name = $stream->expect(\WCML\Twig\Token::NAME_TYPE)->getValue();
                $alias = $name;
                if ($stream->nextIf('as')) {
                    $alias = $stream->expect(\WCML\Twig\Token::NAME_TYPE)->getValue();
                }
                $targets[$name] = new \WCML\Twig\Node\Expression\ConstantExpression($alias, -1);
                if (!$stream->nextIf(\WCML\Twig\Token::PUNCTUATION_TYPE, ',')) {
                    break;
                }
            } while (\true);
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $this->parser->addTrait(new \WCML\Twig\Node\Node(['template' => $template, 'targets' => new \WCML\Twig\Node\Node($targets)]));
        return new \WCML\Twig\Node\Node();
    }
    public function getTag()
    {
        return 'use';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\UseTokenParser', 'WCML\\Twig_TokenParser_Use');
