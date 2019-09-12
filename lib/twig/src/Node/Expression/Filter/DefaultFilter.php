<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node\Expression\Filter;

use WCML\Twig\Compiler;
use WCML\Twig\Node\Expression\ConditionalExpression;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\Expression\FilterExpression;
use WCML\Twig\Node\Expression\GetAttrExpression;
use WCML\Twig\Node\Expression\NameExpression;
use WCML\Twig\Node\Expression\Test\DefinedTest;
use WCML\Twig\Node\Node;
/**
 * Returns the value or the default value when it is undefined or empty.
 *
 *  {{ var.foo|default('foo item on var is not defined') }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DefaultFilter extends \WCML\Twig\Node\Expression\FilterExpression
{
    public function __construct(\WCML\Twig_NodeInterface $node, \WCML\Twig\Node\Expression\ConstantExpression $filterName, \WCML\Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        $default = new \WCML\Twig\Node\Expression\FilterExpression($node, new \WCML\Twig\Node\Expression\ConstantExpression('default', $node->getTemplateLine()), $arguments, $node->getTemplateLine());
        if ('default' === $filterName->getAttribute('value') && ($node instanceof \WCML\Twig\Node\Expression\NameExpression || $node instanceof \WCML\Twig\Node\Expression\GetAttrExpression)) {
            $test = new \WCML\Twig\Node\Expression\Test\DefinedTest(clone $node, 'defined', new \WCML\Twig\Node\Node(), $node->getTemplateLine());
            $false = \count($arguments) ? $arguments->getNode(0) : new \WCML\Twig\Node\Expression\ConstantExpression('', $node->getTemplateLine());
            $node = new \WCML\Twig\Node\Expression\ConditionalExpression($test, $default, $false, $node->getTemplateLine());
        } else {
            $node = $default;
        }
        parent::__construct($node, $filterName, $arguments, $lineno, $tag);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Filter\\DefaultFilter', 'WCML\\Twig_Node_Expression_Filter_Default');
