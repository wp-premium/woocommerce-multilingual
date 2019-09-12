<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node\Expression\Binary;

use WCML\Twig\Compiler;
class MatchesBinary extends \WCML\Twig\Node\Expression\Binary\AbstractBinary
{
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('preg_match(')->subcompile($this->getNode('right'))->raw(', ')->subcompile($this->getNode('left'))->raw(')');
    }
    public function operator(\WCML\Twig\Compiler $compiler)
    {
        return $compiler->raw('');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Binary\\MatchesBinary', 'WCML\\Twig_Node_Expression_Binary_Matches');
