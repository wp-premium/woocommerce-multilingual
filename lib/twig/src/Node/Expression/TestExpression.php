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
use WCML\Twig\TwigTest;
class TestExpression extends \WCML\Twig\Node\Expression\CallExpression
{
    public function __construct(\WCML\Twig_NodeInterface $node, $name, \WCML\Twig_NodeInterface $arguments = null, $lineno)
    {
        $nodes = ['node' => $node];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }
        parent::__construct($nodes, ['name' => $name], $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $test = $compiler->getEnvironment()->getTest($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'test');
        $this->setAttribute('thing', $test);
        if ($test instanceof \WCML\Twig\TwigTest) {
            $this->setAttribute('arguments', $test->getArguments());
        }
        if ($test instanceof \WCML\Twig_TestCallableInterface || $test instanceof \WCML\Twig\TwigTest) {
            $this->setAttribute('callable', $test->getCallable());
        }
        if ($test instanceof \WCML\Twig\TwigTest) {
            $this->setAttribute('is_variadic', $test->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\TestExpression', 'WCML\\Twig_Node_Expression_Test');
