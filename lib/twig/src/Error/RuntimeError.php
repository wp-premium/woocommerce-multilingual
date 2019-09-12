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
namespace WCML\Twig\Error;

/**
 * Exception thrown when an error occurs at runtime.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RuntimeError extends \WCML\Twig\Error\Error
{
}
\class_alias('WCML\\Twig\\Error\\RuntimeError', 'WCML\\Twig_Error_Runtime');
