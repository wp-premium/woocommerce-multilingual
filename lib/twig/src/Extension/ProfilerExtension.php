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

use WCML\Twig\Profiler\NodeVisitor\ProfilerNodeVisitor;
use WCML\Twig\Profiler\Profile;
class ProfilerExtension extends \WCML\Twig\Extension\AbstractExtension
{
    private $actives = [];
    public function __construct(\WCML\Twig\Profiler\Profile $profile)
    {
        $this->actives[] = $profile;
    }
    public function enter(\WCML\Twig\Profiler\Profile $profile)
    {
        $this->actives[0]->addProfile($profile);
        \array_unshift($this->actives, $profile);
    }
    public function leave(\WCML\Twig\Profiler\Profile $profile)
    {
        $profile->leave();
        \array_shift($this->actives);
        if (1 === \count($this->actives)) {
            $this->actives[0]->leave();
        }
    }
    public function getNodeVisitors()
    {
        return [new \WCML\Twig\Profiler\NodeVisitor\ProfilerNodeVisitor(\get_class($this))];
    }
    public function getName()
    {
        return 'profiler';
    }
}
\class_alias('WCML\\Twig\\Extension\\ProfilerExtension', 'WCML\\Twig_Extension_Profiler');
