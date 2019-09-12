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
namespace WCML\Twig\Node\Expression\Unary;

use WCML\Twig\Compiler;
class NegUnary extends \WCML\Twig\Node\Expression\Unary\AbstractUnary
{
    public function operator(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('-');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Unary\\NegUnary', 'WCML\\Twig_Node_Expression_Unary_Neg');
