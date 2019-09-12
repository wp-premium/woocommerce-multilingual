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
namespace WCML\Twig\Node\Expression;

use WCML\Twig\Compiler;
class AssignNameExpression extends \WCML\Twig\Node\Expression\NameExpression
{
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('$context[')->string($this->getAttribute('name'))->raw(']');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\AssignNameExpression', 'WCML\\Twig_Node_Expression_AssignName');
