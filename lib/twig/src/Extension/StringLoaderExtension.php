<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Extension;

use WCML\Twig\TwigFunction;
/**
 * @final
 */
class StringLoaderExtension extends \WCML\Twig\Extension\AbstractExtension
{
    public function getFunctions()
    {
        return [new \WCML\Twig\TwigFunction('template_from_string', 'twig_template_from_string', ['needs_environment' => \true])];
    }
    public function getName()
    {
        return 'string_loader';
    }
}
\class_alias('WCML\\Twig\\Extension\\StringLoaderExtension', 'WCML\\Twig_Extension_StringLoader');
namespace WCML;

use WCML\Twig\Environment;
use WCML\Twig\TemplateWrapper;
/**
 * Loads a template from a string.
 *
 *     {{ include(template_from_string("Hello {{ name }}")) }}
 *
 * @param string $template A template as a string or object implementing __toString()
 * @param string $name     An optional name of the template to be used in error messages
 *
 * @return TemplateWrapper
 */
function twig_template_from_string(\WCML\Twig\Environment $env, $template, $name = null)
{
    return $env->createTemplate((string) $template, $name);
}
