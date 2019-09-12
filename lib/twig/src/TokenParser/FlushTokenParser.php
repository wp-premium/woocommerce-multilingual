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

use WCML\Twig\Node\FlushNode;
use WCML\Twig\Token;
/**
 * Flushes the output to the client.
 *
 * @see flush()
 *
 * @final
 */
class FlushTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $this->parser->getStream()->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        return new \WCML\Twig\Node\FlushNode($token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'flush';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\FlushTokenParser', 'WCML\\Twig_TokenParser_Flush');
