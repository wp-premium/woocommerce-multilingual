<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Sandbox;

use WCML\Twig\Error\Error;
/**
 * Exception thrown when a security error occurs at runtime.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityError extends \WCML\Twig\Error\Error
{
}
\class_alias('WCML\\Twig\\Sandbox\\SecurityError', 'WCML\\Twig_Sandbox_SecurityError');
