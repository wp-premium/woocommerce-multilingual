<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Test;

use WCML\PHPUnit\Framework\TestCase;
use WCML\Twig\Compiler;
use WCML\Twig\Environment;
use WCML\Twig\Loader\ArrayLoader;
use WCML\Twig\Node\Node;
abstract class NodeTestCase extends \WCML\PHPUnit\Framework\TestCase
{
    public abstract function getTests();
    /**
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null, $isPattern = \false)
    {
        $this->assertNodeCompilation($source, $node, $environment, $isPattern);
    }
    public function assertNodeCompilation($source, \WCML\Twig\Node\Node $node, \WCML\Twig\Environment $environment = null, $isPattern = \false)
    {
        $compiler = $this->getCompiler($environment);
        $compiler->compile($node);
        if ($isPattern) {
            $this->assertStringMatchesFormat($source, \trim($compiler->getSource()));
        } else {
            $this->assertEquals($source, \trim($compiler->getSource()));
        }
    }
    protected function getCompiler(\WCML\Twig\Environment $environment = null)
    {
        return new \WCML\Twig\Compiler(null === $environment ? $this->getEnvironment() : $environment);
    }
    protected function getEnvironment()
    {
        return new \WCML\Twig\Environment(new \WCML\Twig\Loader\ArrayLoader([]));
    }
    protected function getVariableGetter($name, $line = \false)
    {
        $line = $line > 0 ? "// line {$line}\n" : '';
        if (\PHP_VERSION_ID >= 70000) {
            return \sprintf('%s($context["%s"] ?? null)', $line, $name);
        }
        if (\PHP_VERSION_ID >= 50400) {
            return \sprintf('%s(isset($context["%s"]) ? $context["%s"] : null)', $line, $name, $name);
        }
        return \sprintf('%s$this->getContext($context, "%s")', $line, $name);
    }
    protected function getAttributeGetter()
    {
        if (\function_exists('WCML\\twig_template_get_attributes')) {
            return 'twig_template_get_attributes($this, ';
        }
        return '$this->getAttribute(';
    }
}
\class_alias('WCML\\Twig\\Test\\NodeTestCase', 'WCML\\Twig_Test_NodeTestCase');
