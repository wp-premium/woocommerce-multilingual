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

use WCML\Twig\Node\WithNode;
use WCML\Twig\Token;
/**
 * Creates a nested scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class WithTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $variables = null;
        $only = \false;
        if (!$stream->test(\WCML\Twig\Token::BLOCK_END_TYPE)) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
            $only = $stream->nextIf(\WCML\Twig\Token::NAME_TYPE, 'only');
        }
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideWithEnd'], \true);
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return new \WCML\Twig\Node\WithNode($body, $variables, $only, $token->getLine(), $this->getTag());
    }
    public function decideWithEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endwith');
    }
    public function getTag()
    {
        return 'with';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\WithTokenParser', 'WCML\\Twig_TokenParser_With');
