<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node\Expression;

use WCML\Twig\Compiler;
class TempNameExpression extends \WCML\Twig\Node\Expression\AbstractExpression
{
    public function __construct($name, $lineno)
    {
        parent::__construct([], ['name' => $name], $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('$_')->raw($this->getAttribute('name'))->raw('_');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\TempNameExpression', 'WCML\\Twig_Node_Expression_TempName');
