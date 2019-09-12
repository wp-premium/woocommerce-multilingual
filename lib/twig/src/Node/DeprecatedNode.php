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
 * Represents a deprecated node.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class DeprecatedNode extends \WCML\Twig\Node\Node
{
    public function __construct(\WCML\Twig\Node\Expression\AbstractExpression $expr, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr], [], $lineno, $tag);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        $expr = $this->getNode('expr');
        if ($expr instanceof \WCML\Twig\Node\Expression\ConstantExpression) {
            $compiler->write('@trigger_error(')->subcompile($expr);
        } else {
            $varName = $compiler->getVarName();
            $compiler->write(\sprintf('$%s = ', $varName))->subcompile($expr)->raw(";\n")->write(\sprintf('@trigger_error($%s', $varName));
        }
        $compiler->raw('.')->string(\sprintf(' ("%s" at line %d).', $this->getTemplateName(), $this->getTemplateLine()))->raw(", E_USER_DEPRECATED);\n");
    }
}
\class_alias('WCML\\Twig\\Node\\DeprecatedNode', 'WCML\\Twig_Node_Deprecated');
