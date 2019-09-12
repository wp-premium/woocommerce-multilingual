<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node;

use WCML\Twig\Compiler;
use WCML\Twig\Node\Expression\AbstractExpression;
use WCML\Twig\Node\Expression\NameExpression;
/**
 * Represents an import node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ImportNode extends \WCML\Twig\Node\Node
{
    public function __construct(\WCML\Twig\Node\Expression\AbstractExpression $expr, \WCML\Twig\Node\Expression\AbstractExpression $var, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr, 'var' => $var], [], $lineno, $tag);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('')->subcompile($this->getNode('var'))->raw(' = ');
        if ($this->getNode('expr') instanceof \WCML\Twig\Node\Expression\NameExpression && '_self' === $this->getNode('expr')->getAttribute('name')) {
            $compiler->raw('$this');
        } else {
            $compiler->raw('$this->loadTemplate(')->subcompile($this->getNode('expr'))->raw(', ')->repr($this->getTemplateName())->raw(', ')->repr($this->getTemplateLine())->raw(')->unwrap()');
        }
        $compiler->raw(";\n");
    }
}
\class_alias('WCML\\Twig\\Node\\ImportNode', 'WCML\\Twig_Node_Import');
