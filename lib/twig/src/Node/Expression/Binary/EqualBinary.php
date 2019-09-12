<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Node\Expression\Binary;

use WCML\Twig\Compiler;
class EqualBinary extends \WCML\Twig\Node\Expression\Binary\AbstractBinary
{
    public function operator(\WCML\Twig\Compiler $compiler)
    {
        return $compiler->raw('==');
    }
}
\class_alias('WCML\\Twig\\Node\\Expression\\Binary\\EqualBinary', 'WCML\\Twig_Node_Expression_Binary_Equal');
