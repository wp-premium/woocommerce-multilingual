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
/**
 * Represents a sandbox node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxNode extends \WCML\Twig\Node\Node
{
    public function __construct(\WCML\Twig_NodeInterface $body, $lineno, $tag = null)
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write("if (!\$alreadySandboxed = \$this->sandbox->isSandboxed()) {\n")->indent()->write("\$this->sandbox->enableSandbox();\n")->outdent()->write("}\n")->subcompile($this->getNode('body'))->write("if (!\$alreadySandboxed) {\n")->indent()->write("\$this->sandbox->disableSandbox();\n")->outdent()->write("}\n");
    }
}
\class_alias('WCML\\Twig\\Node\\SandboxNode', 'WCML\\Twig_Node_Sandbox');
