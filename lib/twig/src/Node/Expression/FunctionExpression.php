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
use WCML\Twig\TwigFunction;
class FunctionExpression extends \WCML\Twig\Node\Expression\CallExpression
{
    public function __construct($name, \WCML\Twig_NodeInterface $arguments, $lineno)
    {
        parent::__construct(['arguments' => $arguments], ['name' => $name, 'is_defined_test' => \false], $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $function = $compiler->getEnvironment()->getFunction($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'function');
        $this->setAttribute('thing', $function);
        $this->setAttribute('needs_environment', $function->needsEnvironment());
        $this->setAttribute('needs_context', $function->needsContext());
        $this->setAttribute('arguments', $function->getArguments());
        if ($function instanceof \WCML\Twig_FunctionCallableInterface || $function instanceof \WCML\Twig\TwigFunction) {
            $callable = $function->getCallable();
            if ('constant' === $name && $this->getAttribute('is_defined_test')) {
                $callable = 'twig_constant_is_defined';
            }
            $this->setAttribute('callable', $callable);
        }
        if ($function instanceof \WCML\Twig\TwigFunction) {
            $this->setAttribute('is_variadic', $function->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\FunctionExpression', 'WCML\\Twig_Node_Expression_Function');
