<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\Profiler\Dumper;

use WCML\Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class TextDumper extends \WCML\Twig\Profiler\Dumper\BaseDumper
{
    protected function formatTemplate(\WCML\Twig\Profiler\Profile $profile, $prefix)
    {
        return \sprintf('%s└ %s', $prefix, $profile->getTemplate());
    }
    protected function formatNonTemplate(\WCML\Twig\Profiler\Profile $profile, $prefix)
    {
        return \sprintf('%s└ %s::%s(%s)', $prefix, $profile->getTemplate(), $profile->getType(), $profile->getName());
    }
    protected function formatTime(\WCML\Twig\Profiler\Profile $profile, $percent)
    {
        return \sprintf('%.2fms/%.0f%%', $profile->getDuration() * 1000, $percent);
    }
}
\class_alias('WCML\\Twig\\Profiler\\Dumper\\TextDumper', 'WCML\\Twig_Profiler_Dumper_Text');
