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

use WCML\Twig\Node\EmbedNode;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\Expression\NameExpression;
use WCML\Twig\Token;
/**
 * Embeds a template.
 *
 * @final
 */
class EmbedTokenParser extends \WCML\Twig\TokenParser\IncludeTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $parent = $this->parser->getExpressionParser()->parseExpression();
        list($variables, $only, $ignoreMissing) = $this->parseArguments();
        $parentToken = $fakeParentToken = new \WCML\Twig\Token(\WCML\Twig\Token::STRING_TYPE, '__parent__', $token->getLine());
        if ($parent instanceof \WCML\Twig\Node\Expression\ConstantExpression) {
            $parentToken = new \WCML\Twig\Token(\WCML\Twig\Token::STRING_TYPE, $parent->getAttribute('value'), $token->getLine());
        } elseif ($parent instanceof \WCML\Twig\Node\Expression\NameExpression) {
            $parentToken = new \WCML\Twig\Token(\WCML\Twig\Token::NAME_TYPE, $parent->getAttribute('name'), $token->getLine());
        }
        // inject a fake parent to make the parent() function work
        $stream->injectTokens([new \WCML\Twig\Token(\WCML\Twig\Token::BLOCK_START_TYPE, '', $token->getLine()), new \WCML\Twig\Token(\WCML\Twig\Token::NAME_TYPE, 'extends', $token->getLine()), $parentToken, new \WCML\Twig\Token(\WCML\Twig\Token::BLOCK_END_TYPE, '', $token->getLine())]);
        $module = $this->parser->parse($stream, [$this, 'decideBlockEnd'], \true);
        // override the parent with the correct one
        if ($fakeParentToken === $parentToken) {
            $module->setNode('parent', $parent);
        }
        $this->parser->embedTemplate($module);
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return new \WCML\Twig\Node\EmbedNode($module->getTemplateName(), $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endembed');
    }
    public function getTag()
    {
        return 'embed';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\EmbedTokenParser', 'WCML\\Twig_TokenParser_Embed');
