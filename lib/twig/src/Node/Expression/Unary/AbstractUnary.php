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
use WCML\Twig\Node\Expression\AbstractExpression;
abstract class AbstractUnary extends \WCML\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\WCML\Twig_NodeInterface $node, $lineno)
    {
        parent::__construct(['node' => $node], [], $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw(' ');
        $this->operator($compiler);
        $compiler->subcompile($this->getNode('node'));
    }
    public abstract function operator(\WCML\Twig\Compiler $compiler);
}
\class_alias('WCML\\Twig\\Node\\Expression\\Unary\\AbstractUnary', 'WCML\\Twig_Node_Expression_Unary');
