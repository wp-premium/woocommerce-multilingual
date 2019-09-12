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
 * Checks that a variable is null.
 *
 *  {{ var is none }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class NullTest extends \WCML\Twig\Node\Expression\TestExpression
{
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('(null === ')->subcompile($this->getNode('node'))->raw(')');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Test\\NullTest', 'WCML\\Twig_Node_Expression_Test_Null');
