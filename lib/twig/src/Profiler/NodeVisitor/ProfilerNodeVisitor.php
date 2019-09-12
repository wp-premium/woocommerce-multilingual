<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Profiler\NodeVisitor;

use WCML\Twig\Environment;
use WCML\Twig\Node\BlockNode;
use WCML\Twig\Node\BodyNode;
use WCML\Twig\Node\MacroNode;
use WCML\Twig\Node\ModuleNode;
use WCML\Twig\Node\Node;
use WCML\Twig\NodeVisitor\AbstractNodeVisitor;
use WCML\Twig\Profiler\Node\EnterProfileNode;
use WCML\Twig\Profiler\Node\LeaveProfileNode;
use WCML\Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class ProfilerNodeVisitor extends \WCML\Twig\NodeVisitor\AbstractNodeVisitor
{
    private $extensionName;
    public function __construct($extensionName)
    {
        $this->extensionName = $extensionName;
    }
    protected function doEnterNode(\WCML\Twig\Node\Node $node, \WCML\Twig\Environment $env)
    {
        return $node;
    }
    protected function doLeaveNode(\WCML\Twig\Node\Node $node, \WCML\Twig\Environment $env)
    {
        if ($node instanceof \WCML\Twig\Node\ModuleNode) {
            $varName = $this->getVarName();
            $node->setNode('display_start', new \WCML\Twig\Node\Node([new \WCML\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \WCML\Twig\Profiler\Profile::TEMPLATE, $node->getTemplateName(), $varName), $node->getNode('display_start')]));
            $node->setNode('display_end', new \WCML\Twig\Node\Node([new \WCML\Twig\Profiler\Node\LeaveProfileNode($varName), $node->getNode('display_end')]));
        } elseif ($node instanceof \WCML\Twig\Node\BlockNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \WCML\Twig\Node\BodyNode([new \WCML\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \WCML\Twig\Profiler\Profile::BLOCK, $node->getAttribute('name'), $varName), $node->getNode('body'), new \WCML\Twig\Profiler\Node\LeaveProfileNode($varName)]));
        } elseif ($node instanceof \WCML\Twig\Node\MacroNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \WCML\Twig\Node\BodyNode([new \WCML\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \WCML\Twig\Profiler\Profile::MACRO, $node->getAttribute('name'), $varName), $node->getNode('body'), new \WCML\Twig\Profiler\Node\LeaveProfileNode($varName)]));
        }
        return $node;
    }
    private function getVarName()
    {
        return \sprintf('__internal_%s', \hash('sha256', $this->extensionName));
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('WCML\\Twig\\Profiler\\NodeVisitor\\ProfilerNodeVisitor', 'WCML\\Twig_Profiler_NodeVisitor_Profiler');
