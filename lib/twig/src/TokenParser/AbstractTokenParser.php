<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WCML\Twig\TokenParser;

use WCML\Twig\Parser;
/**
 * Base class for all token parsers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractTokenParser implements \WCML\Twig\TokenParser\TokenParserInterface
{
    /**
     * @var Parser
     */
    protected $parser;
    public function setParser(\WCML\Twig\Parser $parser)
    {
        $this->parser = $parser;
    }
}
\class_alias('WCML\\Twig\\TokenParser\\AbstractTokenParser', 'WCML\\Twig_TokenParser');
