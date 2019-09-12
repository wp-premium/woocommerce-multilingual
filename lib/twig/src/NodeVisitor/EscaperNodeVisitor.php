<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\NodeVisitor;

use WCML\Twig\Environment;
use WCML\Twig\Node\AutoEscapeNode;
use WCML\Twig\Node\BlockNode;
use WCML\Twig\Node\BlockReferenceNode;
use WCML\Twig\Node\DoNode;
use WCML\Twig\Node\Expression\ConditionalExpression;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\Expression\FilterExpression;
use WCML\Twig\Node\Expression\InlinePrint;
use WCML\Twig\Node\ImportNode;
use WCML\Twig\Node\ModuleNode;
use WCML\Twig\Node\Node;
use WCML\Twig\Node\PrintNode;
use WCML\Twig\NodeTraverser;
/**
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class EscaperNodeVisitor extends \WCML\Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $statusStack = [];
    protected $blocks = [];
    protected $safeAnalysis;
    protected $traverser;
    protected $defaultStrategy = \false;
    protected $safeVars = [];
    public function __construct()
    {
        $this->safeAnalysis = new \WCML\Twig\NodeVisitor\SafeAnalysisNodeVisitor();
    }
    protected function doEnterNode(\WCML\Twig\Node\Node $node, \WCML\Twig\Environment $env)
    {
        if ($node instanceof \WCML\Twig\Node\ModuleNode) {
            if ($env->hasExtension('WCML\\Twig\\Extension\\EscaperExtension') && ($defaultStrategy = $env->getExtension('WCML\\Twig\\Extension\\EscaperExtension')->getDefaultStrategy($node->getTemplateName()))) {
                $this->defaultStrategy = $defaultStrategy;
            }
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \WCML\Twig\Node\AutoEscapeNode) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof \WCML\Twig\Node\BlockNode) {
            $this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping($env);
        } elseif ($node instanceof \WCML\Twig\Node\ImportNode) {
            $this->safeVars[] = $node->getNode('var')->getAttribute('name');
        }
        return $node;
    }
    protected function doLeaveNode(\WCML\Twig\Node\Node $node, \WCML\Twig\Environment $env)
    {
        if ($node instanceof \WCML\Twig\Node\ModuleNode) {
            $this->defaultStrategy = \false;
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \WCML\Twig\Node\Expression\FilterExpression) {
            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof \WCML\Twig\Node\PrintNode && \false !== ($type = $this->needEscaping($env))) {
            $expression = $node->getNode('expr');
            if ($expression instanceof \WCML\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expression, $env, $type)) {
                return new \WCML\Twig\Node\DoNode($this->unwrapConditional($expression, $env, $type), $expression->getTemplateLine());
            }
            return $this->escapePrintNode($node, $env, $type);
        }
        if ($node instanceof \WCML\Twig\Node\AutoEscapeNode || $node instanceof \WCML\Twig\Node\BlockNode) {
            \array_pop($this->statusStack);
        } elseif ($node instanceof \WCML\Twig\Node\BlockReferenceNode) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping($env);
        }
        return $node;
    }
    private function shouldUnwrapConditional(\WCML\Twig\Node\Expression\ConditionalExpression $expression, \WCML\Twig\Environment $env, $type)
    {
        $expr2Safe = $this->isSafeFor($type, $expression->getNode('expr2'), $env);
        $expr3Safe = $this->isSafeFor($type, $expression->getNode('expr3'), $env);
        return $expr2Safe !== $expr3Safe;
    }
    private function unwrapConditional(\WCML\Twig\Node\Expression\ConditionalExpression $expression, \WCML\Twig\Environment $env, $type)
    {
        // convert "echo a ? b : c" to "a ? echo b : echo c" recursively
        $expr2 = $expression->getNode('expr2');
        if ($expr2 instanceof \WCML\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expr2, $env, $type)) {
            $expr2 = $this->unwrapConditional($expr2, $env, $type);
        } else {
            $expr2 = $this->escapeInlinePrintNode(new \WCML\Twig\Node\Expression\InlinePrint($expr2, $expr2->getTemplateLine()), $env, $type);
        }
        $expr3 = $expression->getNode('expr3');
        if ($expr3 instanceof \WCML\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expr3, $env, $type)) {
            $expr3 = $this->unwrapConditional($expr3, $env, $type);
        } else {
            $expr3 = $this->escapeInlinePrintNode(new \WCML\Twig\Node\Expression\InlinePrint($expr3, $expr3->getTemplateLine()), $env, $type);
        }
        return new \WCML\Twig\Node\Expression\ConditionalExpression($expression->getNode('expr1'), $expr2, $expr3, $expression->getTemplateLine());
    }
    private function escapeInlinePrintNode(\WCML\Twig\Node\Expression\InlinePrint $node, \WCML\Twig\Environment $env, $type)
    {
        $expression = $node->getNode('node');
        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }
        return new \WCML\Twig\Node\Expression\InlinePrint($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }
    protected function escapePrintNode(\WCML\Twig\Node\PrintNode $node, \WCML\Twig\Environment $env, $type)
    {
        if (\false === $type) {
            return $node;
        }
        $expression = $node->getNode('expr');
        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }
        $class = \get_class($node);
        return new $class($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }
    protected function preEscapeFilterNode(\WCML\Twig\Node\Expression\FilterExpression $filter, \WCML\Twig\Environment $env)
    {
        $name = $filter->getNode('filter')->getAttribute('value');
        $type = $env->getFilter($name)->getPreEscape();
        if (null === $type) {
            return $filter;
        }
        $node = $filter->getNode('node');
        if ($this->isSafeFor($type, $node, $env)) {
            return $filter;
        }
        $filter->setNode('node', $this->getEscaperFilter($type, $node));
        return $filter;
    }
    protected function isSafeFor($type, \WCML\Twig_NodeInterface $expression, $env)
    {
        $safe = $this->safeAnalysis->getSafe($expression);
        if (null === $safe) {
            if (null === $this->traverser) {
                $this->traverser = new \WCML\Twig\NodeTraverser($env, [$this->safeAnalysis]);
            }
            $this->safeAnalysis->setSafeVars($this->safeVars);
            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }
        return \in_array($type, $safe) || \in_array('all', $safe);
    }
    protected function needEscaping(\WCML\Twig\Environment $env)
    {
        if (\count($this->statusStack)) {
            return $this->statusStack[\count($this->statusStack) - 1];
        }
        return $this->defaultStrategy ? $this->defaultStrategy : \false;
    }
    protected function getEscaperFilter($type, \WCML\Twig_NodeInterface $node)
    {
        $line = $node->getTemplateLine();
        $name = new \WCML\Twig\Node\Expression\ConstantExpression('escape', $line);
        $args = new \WCML\Twig\Node\Node([new \WCML\Twig\Node\Expression\ConstantExpression((string) $type, $line), new \WCML\Twig\Node\Expression\ConstantExpression(null, $line), new \WCML\Twig\Node\Expression\ConstantExpression(\true, $line)]);
        return new \WCML\Twig\Node\Expression\FilterExpression($node, $name, $args, $line);
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('WCML\\Twig\\NodeVisitor\\EscaperNodeVisitor', 'WCML\\Twig_NodeVisitor_Escaper');
