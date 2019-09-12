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

use WCML\Twig\Error\SyntaxError;
use WCML\Twig\Node\IncludeNode;
use WCML\Twig\Node\SandboxNode;
use WCML\Twig\Node\TextNode;
use WCML\Twig\Token;
/**
 * Marks a section of a template as untrusted code that must be evaluated in the sandbox mode.
 *
 *    {% sandbox %}
 *        {% include 'user.html' %}
 *    {% endsandbox %}
 *
 * @see https://twig.symfony.com/doc/api.html#sandbox-extension for details
 *
 * @final
 */
class SandboxTokenParser extends \WCML\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\WCML\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        $stream->expect(\WCML\Twig\Token::BLOCK_END_TYPE);
        // in a sandbox tag, only include tags are allowed
        if (!$body instanceof \WCML\Twig\Node\IncludeNode) {
            foreach ($body as $node) {
                if ($node instanceof \WCML\Twig\Node\TextNode && \ctype_space($node->getAttribute('data'))) {
                    continue;
                }
                if (!$node instanceof \WCML\Twig\Node\IncludeNode) {
                    throw new \WCML\Twig\Error\SyntaxError('Only "include" tags are allowed within a "sandbox" section.', $node->getTemplateLine(), $stream->getSourceContext());
                }
            }
        }
        return new \WCML\Twig\Node\SandboxNode($body, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(\WCML\Twig\Token $token)
    {
        return $token->test('endsandbox');
    }
    public function getTag()
    {
        return 'sandbox';
    }
}
\class_alias('WCML\\Twig\\TokenParser\\SandboxTokenParser', 'WCML\\Twig_TokenParser_Sandbox');
