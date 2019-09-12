<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node\Expression;

use WCML\Twig\Compiler;
use WCML\Twig\Node\Node;
/**
 * @internal
 */
final class InlinePrint extends \WCML\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\WCML\Twig\Node\Node $node, $lineno)
    {
        parent::__construct(['node' => $node], [], $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->raw('print (')->subcompile($this->getNode('node'))->raw(')');
    }
}
