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
use WCML\Twig\Node\Expression\ConstantExpression;
/**
 * Represents an embed node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class EmbedNode extends \WCML\Twig\Node\IncludeNode
{
    // we don't inject the module to avoid node visitors to traverse it twice (as it will be already visited in the main module)
    public function __construct($name, $index, \WCML\Twig\Node\Expression\AbstractExpression $variables = null, $only = \false, $ignoreMissing = \false, $lineno, $tag = null)
    {
        parent::__construct(new \WCML\Twig\Node\Expression\ConstantExpression('not_used', $lineno), $variables, $only, $ignoreMissing, $lineno, $tag);
        $this->setAttribute('name', $name);
        // to be removed in 2.0, used name instead
        $this->setAttribute('filename', $name);
        $this->setAttribute('index', $index);
    }
    protected function addGetTemplate(\WCML\Twig\Compiler $compiler)
    {
        $compiler->write('$this->loadTemplate(')->string($this->getAttribute('name'))->raw(', ')->repr($this->getTemplateName())->raw(', ')->repr($this->getTemplateLine())->raw(', ')->string($this->getAttribute('index'))->raw(')');
    }
}
\class_alias('WCML\\Twig\\Node\\EmbedNode', 'WCML\\Twig_Node_Embed');
