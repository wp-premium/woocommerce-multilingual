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
use WCML\Twig\Node\Expression\Binary\AndBinary;
use WCML\Twig\Node\Expression\Test\DefinedTest;
use WCML\Twig\Node\Expression\Test\NullTest;
use WCML\Twig\Node\Expression\Unary\NotUnary;
use WCML\Twig\Node\Node;
class NullCoalesceExpression extends \WCML\Twig\Node\Expression\ConditionalExpression
{
    public function __construct(\WCML\Twig_NodeInterface $left, \WCML\Twig_NodeInterface $right, $lineno)
    {
        $test = new \WCML\Twig\Node\Expression\Binary\AndBinary(new \WCML\Twig\Node\Expression\Test\DefinedTest(clone $left, 'defined', new \WCML\Twig\Node\Node(), $left->getTemplateLine()), new \WCML\Twig\Node\Expression\Unary\NotUnary(new \WCML\Twig\Node\Expression\Test\NullTest($left, 'null', new \WCML\Twig\Node\Node(), $left->getTemplateLine()), $left->getTemplateLine()), $left->getTemplateLine());
        parent::__construct($test, $left, $right, $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        /*
         * This optimizes only one case. PHP 7 also supports more complex expressions
         * that can return null. So, for instance, if log is defined, log("foo") ?? "..." works,
         * but log($a["foo"]) ?? "..." does not if $a["foo"] is not defined. More advanced
         * cases might be implemented as an optimizer node visitor, but has not been done
         * as benefits are probably not worth the added complexity.
         */
        if (\PHP_VERSION_ID >= 70000 && $this->getNode('expr2') instanceof \WCML\Twig\Node\Expression\NameExpression) {
            $this->getNode('expr2')->setAttribute('always_defined', \true);
            $compiler->raw('((')->subcompile($this->getNode('expr2'))->raw(') ?? (')->subcompile($this->getNode('expr3'))->raw('))');
        } else {
            parent::compile($compiler);
        }
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\NullCoalesceExpression', 'WCML\\Twig_Node_Expression_NullCoalesce');
