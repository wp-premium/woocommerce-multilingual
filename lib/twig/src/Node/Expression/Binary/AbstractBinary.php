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
namespace WCML\Twig\Node\Expression\Binary;

use WCML\Twig\Compiler;
use WCML\Twig\Node\Expression\AbstractExpression;
abstract class AbstractBinary extends \WCML\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\WCML\Twig_NodeInterface $left, \WCML\Twig_NodeInterface $right, $lineno)
    {
        parent::__construct(['left' => $left, 'right' => $right], [], $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('(')->subcompile($this->getNode('left'))->raw(' ');
        $this->operator($compiler);
        $compiler->raw(' ')->subcompile($this->getNode('right'))->raw(')');
    }
    public abstract function operator(\WCML\Twig\Compiler $compiler);
}
\class_alias('WCML\\Twig\\Node\\Expression\\Binary\\AbstractBinary', 'WCML\\Twig_Node_Expression_Binary');
