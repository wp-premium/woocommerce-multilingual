<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node;

use WCML\Twig\Compiler;
/**
 * Represents a text node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TextNode extends \WCML\Twig\Node\Node implements \WCML\Twig\Node\NodeOutputInterface
{
    public function __construct($data, $lineno)
    {
        parent::__construct([], ['data' => $data], $lineno);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ')->string($this->getAttribute('data'))->raw(";\n");
    }
}
\class_alias('WCML\\Twig\\Node\\TextNode', 'WCML\\Twig_Node_Text');
