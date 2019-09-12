<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node\Expression\Test;

use WCML\Twig\Compiler;
use WCML\Twig\Error\SyntaxError;
use WCML\Twig\Node\Expression\ArrayExpression;
use WCML\Twig\Node\Expression\BlockReferenceExpression;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\Expression\FunctionExpression;
use WCML\Twig\Node\Expression\GetAttrExpression;
use WCML\Twig\Node\Expression\NameExpression;
use WCML\Twig\Node\Expression\TestExpression;
/**
 * Checks if a variable is defined in the current context.
 *
 *    {# defined works with variable names and variable attributes #}
 *    {% if foo is defined %}
 *        {# ... #}
 *    {% endif %}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DefinedTest extends \WCML\Twig\Node\Expression\TestExpression
{
    public function __construct(\WCML\Twig_NodeInterface $node, $name, \WCML\Twig_NodeInterface $arguments = null, $lineno)
    {
        if ($node instanceof \WCML\Twig\Node\Expression\NameExpression) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \WCML\Twig\Node\Expression\GetAttrExpression) {
            $node->setAttribute('is_defined_test', \true);
            $this->changeIgnoreStrictCheck($node);
        } elseif ($node instanceof \WCML\Twig\Node\Expression\BlockReferenceExpression) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \WCML\Twig\Node\Expression\FunctionExpression && 'constant' === $node->getAttribute('name')) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \WCML\Twig\Node\Expression\ConstantExpression || $node instanceof \WCML\Twig\Node\Expression\ArrayExpression) {
            $node = new \WCML\Twig\Node\Expression\ConstantExpression(\true, $node->getTemplateLine());
        } else {
            throw new \WCML\Twig\Error\SyntaxError('The "defined" test only works with simple variables.', $lineno);
        }
        parent::__construct($node, $name, $arguments, $lineno);
    }
    protected function changeIgnoreStrictCheck(\WCML\Twig\Node\Expression\GetAttrExpression $node)
    {
        $node->setAttribute('ignore_strict_check', \true);
        if ($node->getNode('node') instanceof \WCML\Twig\Node\Expression\GetAttrExpression) {
            $this->changeIgnoreStrictCheck($node->getNode('node'));
        }
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Test\\DefinedTest', 'WCML\\Twig_Node_Expression_Test_Defined');
