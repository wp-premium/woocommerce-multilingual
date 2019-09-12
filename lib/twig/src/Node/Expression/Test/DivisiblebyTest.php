<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node\Expression\Test;

use WCML\Twig\Compiler;
use WCML\Twig\Node\Expression\TestExpression;
/**
 * Checks if a variable is divisible by a number.
 *
 *  {% if loop.index is divisible by(3) %}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DivisiblebyTest extends \WCML\Twig\Node\Expression\TestExpression
{
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('(0 == ')->subcompile($this->getNode('node'))->raw(' % ')->subcompile($this->getNode('arguments')->getNode(0))->raw(')');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Test\\DivisiblebyTest', 'WCML\\Twig_Node_Expression_Test_Divisibleby');
