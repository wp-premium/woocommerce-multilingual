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
 * Checks if a number is odd.
 *
 *  {{ var is odd }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class OddTest extends \WCML\Twig\Node\Expression\TestExpression
{
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('(')->subcompile($this->getNode('node'))->raw(' % 2 == 1')->raw(')');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Test\\OddTest', 'WCML\\Twig_Node_Expression_Test_Odd');
