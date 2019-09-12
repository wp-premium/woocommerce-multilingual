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
use WCML\Twig\Node\CheckSecurityNode;
use WCML\Twig\Node\CheckToStringNode;
use WCML\Twig\Node\Expression\Binary\ConcatBinary;
use WCML\Twig\Node\Expression\Binary\RangeBinary;
use WCML\Twig\Node\Expression\FilterExpression;
use WCML\Twig\Node\Expression\FunctionExpression;
use WCML\Twig\Node\Expression\GetAttrExpression;
use WCML\Twig\Node\Expression\NameExpression;
use WCML\Twig\Node\ModuleNode;
use WCML\Twig\Node\Node;
use WCML\Twig\Node\PrintNode;
use WCML\Twig\Node\SetNode;
/**
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxNodeVisitor extends \WCML\Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $inAModule = \false;
    protected $tags;
    protected $filters;
    protected $functions;
    private $needsToStringWrap = \false;
    protected function doEnterNode(\WCML\Twig\Node\Node $node, \WCML\Twig\Environment $env)
    {
        if ($node instanceof \WCML\Twig\Node\ModuleNode) {
            $this->inAModule = \true;
            $this->tags = [];
            $this->filters = [];
            $this->functions = [];
            return $node;
        } elseif ($this->inAModule) {
            // look for tags
            if ($node->getNodeTag() && !isset($this->tags[$node->getNodeTag()])) {
                $this->tags[$node->getNodeTag()] = $node;
            }
            // look for filters
            if ($node instanceof \WCML\Twig\Node\Expression\FilterExpression && !isset($this->filters[$node->getNode('filter')->getAttribute('value')])) {
                $this->filters[$node->getNode('filter')->getAttribute('value')] = $node;
            }
            // look for functions
            if ($node instanceof \WCML\Twig\Node\Expression\FunctionExpression && !isset($this->functions[$node->getAttribute('name')])) {
                $this->functions[$node->getAttribute('name')] = $node;
            }
            // the .. operator is equivalent to the range() function
            if ($node instanceof \WCML\Twig\Node\Expression\Binary\RangeBinary && !isset($this->functions['range'])) {
                $this->functions['range'] = $node;
            }
            if ($node instanceof \WCML\Twig\Node\PrintNode) {
                $this->needsToStringWrap = \true;
                $this->wrapNode($node, 'expr');
            }
            if ($node instanceof \WCML\Twig\Node\SetNode && !$node->getAttribute('capture')) {
                $this->needsToStringWrap = \true;
            }
            // wrap outer nodes that can implicitly call __toString()
            if ($this->needsToStringWrap) {
                if ($node instanceof \WCML\Twig\Node\Expression\Binary\ConcatBinary) {
                    $this->wrapNode($node, 'left');
                    $this->wrapNode($node, 'right');
                }
                if ($node instanceof \WCML\Twig\Node\Expression\FilterExpression) {
                    $this->wrapNode($node, 'node');
                    $this->wrapArrayNode($node, 'arguments');
                }
                if ($node instanceof \WCML\Twig\Node\Expression\FunctionExpression) {
                    $this->wrapArrayNode($node, 'arguments');
                }
            }
        }
        return $node;
    }
    protected function doLeaveNode(\WCML\Twig\Node\Node $node, \WCML\Twig\Environment $env)
    {
        if ($node instanceof \WCML\Twig\Node\ModuleNode) {
            $this->inAModule = \false;
            $node->getNode('constructor_end')->setNode('_security_check', new \WCML\Twig\Node\Node([new \WCML\Twig\Node\CheckSecurityNode($this->filters, $this->tags, $this->functions), $node->getNode('display_start')]));
        } elseif ($this->inAModule) {
            if ($node instanceof \WCML\Twig\Node\PrintNode || $node instanceof \WCML\Twig\Node\SetNode) {
                $this->needsToStringWrap = \false;
            }
        }
        return $node;
    }
    private function wrapNode(\WCML\Twig\Node\Node $node, $name)
    {
        $expr = $node->getNode($name);
        if ($expr instanceof \WCML\Twig\Node\Expression\NameExpression || $expr instanceof \WCML\Twig\Node\Expression\GetAttrExpression) {
            $node->setNode($name, new \WCML\Twig\Node\CheckToStringNode($expr));
        }
    }
    private function wrapArrayNode(\WCML\Twig\Node\Node $node, $name)
    {
        $args = $node->getNode($name);
        foreach ($args as $name => $_) {
            $this->wrapNode($args, $name);
        }
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('WCML\\Twig\\NodeVisitor\\SandboxNodeVisitor', 'WCML\\Twig_NodeVisitor_Sandbox');
