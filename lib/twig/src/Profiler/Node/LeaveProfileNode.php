<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Profiler\Node;

use WCML\Twig\Compiler;
use WCML\Twig\Node\Node;
/**
 * Represents a profile leave node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LeaveProfileNode extends \WCML\Twig\Node\Node
{
    public function __construct($varName)
    {
        parent::__construct([], ['var_name' => $varName]);
    }
    public function compile(\WCML\Twig\Compiler $compiler)
    {
        $compiler->write("\n")->write(\sprintf("\$%s->leave(\$%s);\n\n", $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'));
    }
}
\class_alias('WCML\\Twig\\Profiler\\Node\\LeaveProfileNode', 'WCML\\Twig_Profiler_Node_LeaveProfile');
