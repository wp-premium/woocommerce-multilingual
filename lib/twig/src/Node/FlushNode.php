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
 * Represents a flush node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FlushNode extends \WCML\Twig\Node\Node
{
    public function __construct($lineno, $tag)
    {
        parent::__construct([], [], $lineno, $tag);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write("flush();\n");
    }
}
\class_alias('WCML\\Twig\\Node\\FlushNode', 'WCML\\Twig_Node_Flush');
