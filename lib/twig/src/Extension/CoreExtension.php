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

use WCML\Twig\ExpressionParser;
use WCML\Twig\TokenParser\ApplyTokenParser;
use WCML\Twig\TokenParser\BlockTokenParser;
use WCML\Twig\TokenParser\DeprecatedTokenParser;
use WCML\Twig\TokenParser\DoTokenParser;
use WCML\Twig\TokenParser\EmbedTokenParser;
use WCML\Twig\TokenParser\ExtendsTokenParser;
use WCML\Twig\TokenParser\FilterTokenParser;
use WCML\Twig\TokenParser\FlushTokenParser;
use WCML\Twig\TokenParser\ForTokenParser;
use WCML\Twig\TokenParser\FromTokenParser;
use WCML\Twig\TokenParser\IfTokenParser;
use WCML\Twig\TokenParser\ImportTokenParser;
use WCML\Twig\TokenParser\IncludeTokenParser;
use WCML\Twig\TokenParser\MacroTokenParser;
use WCML\Twig\TokenParser\SetTokenParser;
use WCML\Twig\TokenParser\SpacelessTokenParser;
use WCML\Twig\TokenParser\UseTokenParser;
use WCML\Twig\TokenParser\WithTokenParser;
use WCML\Twig\TwigFilter;
use WCML\Twig\TwigFunction;
use WCML\Twig\TwigTest;
/**
 * @final
 */
class CoreExtension extends \WCML\Twig\Extension\AbstractExtension
{
    protected $dateFormats = ['F j, Y H:i', '%d days'];
    protected $numberFormat = [0, '.', ','];
    protected $timezone = null;
    protected $escapers = [];
    /**
     * Defines a new escaper to be used via the escape filter.
     *
     * @param string   $strategy The strategy name that should be used as a strategy in the escape call
     * @param callable $callable A valid PHP callable
     */
    public function setEscaper($strategy, $callable)
    {
        $this->escapers[$strategy] = $callable;
    }
    /**
     * Gets all defined escapers.
     *
     * @return array An array of escapers
     */
    public function getEscapers()
    {
        return $this->escapers;
    }
    /**
     * Sets the default format to be used by the date filter.
     *
     * @param string $format             The default date format string
     * @param string $dateIntervalFormat The default date interval format string
     */
    public function setDateFormat($format = null, $dateIntervalFormat = null)
    {
        if (null !== $format) {
            $this->dateFormats[0] = $format;
        }
        if (null !== $dateIntervalFormat) {
            $this->dateFormats[1] = $dateIntervalFormat;
        }
    }
    /**
     * Gets the default format to be used by the date filter.
     *
     * @return array The default date format string and the default date interval format string
     */
    public function getDateFormat()
    {
        return $this->dateFormats;
    }
    /**
     * Sets the default timezone to be used by the date filter.
     *
     * @param \DateTimeZone|string $timezone The default timezone string or a \DateTimeZone object
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
    }
    /**
     * Gets the default timezone to be used by the date filter.
     *
     * @return \DateTimeZone The default timezone currently in use
     */
    public function getTimezone()
    {
        if (null === $this->timezone) {
            $this->timezone = new \DateTimeZone(\date_default_timezone_get());
        }
        return $this->timezone;
    }
    /**
     * Sets the default format to be used by the number_format filter.
     *
     * @param int    $decimal      the number of decimal places to use
     * @param string $decimalPoint the character(s) to use for the decimal point
     * @param string $thousandSep  the character(s) to use for the thousands separator
     */
    public function setNumberFormat($decimal, $decimalPoint, $thousandSep)
    {
        $this->numberFormat = [$decimal, $decimalPoint, $thousandSep];
    }
    /**
     * Get the default format used by the number_format filter.
     *
     * @return array The arguments for number_format()
     */
    public function getNumberFormat()
    {
        return $this->numberFormat;
    }
    public function getTokenParsers()
    {
        return [new \WCML\Twig\TokenParser\ApplyTokenParser(), new \WCML\Twig\TokenParser\ForTokenParser(), new \WCML\Twig\TokenParser\IfTokenParser(), new \WCML\Twig\TokenParser\ExtendsTokenParser(), new \WCML\Twig\TokenParser\IncludeTokenParser(), new \WCML\Twig\TokenParser\BlockTokenParser(), new \WCML\Twig\TokenParser\UseTokenParser(), new \WCML\Twig\TokenParser\FilterTokenParser(), new \WCML\Twig\TokenParser\MacroTokenParser(), new \WCML\Twig\TokenParser\ImportTokenParser(), new \WCML\Twig\TokenParser\FromTokenParser(), new \WCML\Twig\TokenParser\SetTokenParser(), new \WCML\Twig\TokenParser\SpacelessTokenParser(), new \WCML\Twig\TokenParser\FlushTokenParser(), new \WCML\Twig\TokenParser\DoTokenParser(), new \WCML\Twig\TokenParser\EmbedTokenParser(), new \WCML\Twig\TokenParser\WithTokenParser(), new \WCML\Twig\TokenParser\DeprecatedTokenParser()];
    }
    public function getFilters()
    {
        $filters = [
            // formatting filters
            new \WCML\Twig\TwigFilter('date', '\\WCML\\twig_date_format_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('date_modify', '\\WCML\\twig_date_modify_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('format', 'sprintf'),
            new \WCML\Twig\TwigFilter('replace', '\\WCML\\twig_replace_filter'),
            new \WCML\Twig\TwigFilter('number_format', '\\WCML\\twig_number_format_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('abs', 'abs'),
            new \WCML\Twig\TwigFilter('round', '\\WCML\\twig_round'),
            // encoding
            new \WCML\Twig\TwigFilter('url_encode', '\\WCML\\twig_urlencode_filter'),
            new \WCML\Twig\TwigFilter('json_encode', '\\WCML\\twig_jsonencode_filter'),
            new \WCML\Twig\TwigFilter('convert_encoding', '\\WCML\\twig_convert_encoding'),
            // string filters
            new \WCML\Twig\TwigFilter('title', '\\WCML\\twig_title_string_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('capitalize', '\\WCML\\twig_capitalize_string_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('upper', 'strtoupper'),
            new \WCML\Twig\TwigFilter('lower', 'strtolower'),
            new \WCML\Twig\TwigFilter('striptags', 'strip_tags'),
            new \WCML\Twig\TwigFilter('trim', '\\WCML\\twig_trim_filter'),
            new \WCML\Twig\TwigFilter('nl2br', 'nl2br', ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new \WCML\Twig\TwigFilter('spaceless', '\\WCML\\twig_spaceless', ['is_safe' => ['html']]),
            // array helpers
            new \WCML\Twig\TwigFilter('join', '\\WCML\\twig_join_filter'),
            new \WCML\Twig\TwigFilter('split', '\\WCML\\twig_split_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('sort', '\\WCML\\twig_sort_filter'),
            new \WCML\Twig\TwigFilter('merge', '\\WCML\\twig_array_merge'),
            new \WCML\Twig\TwigFilter('batch', '\\WCML\\twig_array_batch'),
            new \WCML\Twig\TwigFilter('filter', '\\WCML\\twig_array_filter'),
            new \WCML\Twig\TwigFilter('map', '\\WCML\\twig_array_map'),
            new \WCML\Twig\TwigFilter('reduce', '\\WCML\\twig_array_reduce'),
            // string/array filters
            new \WCML\Twig\TwigFilter('reverse', '\\WCML\\twig_reverse_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('length', '\\WCML\\twig_length_filter', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('slice', '\\WCML\\twig_slice', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('first', '\\WCML\\twig_first', ['needs_environment' => \true]),
            new \WCML\Twig\TwigFilter('last', '\\WCML\\twig_last', ['needs_environment' => \true]),
            // iteration and runtime
            new \WCML\Twig\TwigFilter('default', '\\WCML\\_twig_default_filter', ['node_class' => 'WCML\\Twig\\Node\\Expression\\Filter\\DefaultFilter']),
            new \WCML\Twig\TwigFilter('keys', '\\WCML\\twig_get_array_keys_filter'),
            // escaping
            new \WCML\Twig\TwigFilter('escape', '\\WCML\\twig_escape_filter', ['needs_environment' => \true, 'is_safe_callback' => '\\WCML\\twig_escape_filter_is_safe']),
            new \WCML\Twig\TwigFilter('e', '\\WCML\\twig_escape_filter', ['needs_environment' => \true, 'is_safe_callback' => '\\WCML\\twig_escape_filter_is_safe']),
        ];
        if (\function_exists('mb_get_info')) {
            $filters[] = new \WCML\Twig\TwigFilter('upper', '\\WCML\\twig_upper_filter', ['needs_environment' => \true]);
            $filters[] = new \WCML\Twig\TwigFilter('lower', '\\WCML\\twig_lower_filter', ['needs_environment' => \true]);
        }
        return $filters;
    }
    public function getFunctions()
    {
        return [new \WCML\Twig\TwigFunction('max', 'max'), new \WCML\Twig\TwigFunction('min', 'min'), new \WCML\Twig\TwigFunction('range', 'range'), new \WCML\Twig\TwigFunction('constant', 'twig_constant'), new \WCML\Twig\TwigFunction('cycle', 'twig_cycle'), new \WCML\Twig\TwigFunction('random', 'twig_random', ['needs_environment' => \true]), new \WCML\Twig\TwigFunction('date', 'twig_date_converter', ['needs_environment' => \true]), new \WCML\Twig\TwigFunction('include', 'twig_include', ['needs_environment' => \true, 'needs_context' => \true, 'is_safe' => ['all']]), new \WCML\Twig\TwigFunction('source', 'twig_source', ['needs_environment' => \true, 'is_safe' => ['all']])];
    }
    public function getTests()
    {
        return [new \WCML\Twig\TwigTest('even', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\EvenTest']), new \WCML\Twig\TwigTest('odd', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\OddTest']), new \WCML\Twig\TwigTest('defined', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\DefinedTest']), new \WCML\Twig\TwigTest('sameas', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\SameasTest', 'deprecated' => '1.21', 'alternative' => 'same as']), new \WCML\Twig\TwigTest('same as', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\SameasTest']), new \WCML\Twig\TwigTest('none', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\NullTest']), new \WCML\Twig\TwigTest('null', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\NullTest']), new \WCML\Twig\TwigTest('divisibleby', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\DivisiblebyTest', 'deprecated' => '1.21', 'alternative' => 'divisible by']), new \WCML\Twig\TwigTest('divisible by', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\DivisiblebyTest']), new \WCML\Twig\TwigTest('constant', null, ['node_class' => 'WCML\\Twig\\Node\\Expression\\Test\\ConstantTest']), new \WCML\Twig\TwigTest('empty', 'twig_test_empty'), new \WCML\Twig\TwigTest('iterable', 'twig_test_iterable')];
    }
    public function getOperators()
    {
        return [['not' => ['precedence' => 50, 'class' => 'WCML\\Twig\\Node\\Expression\\Unary\\NotUnary'], '-' => ['precedence' => 500, 'class' => 'WCML\\Twig\\Node\\Expression\\Unary\\NegUnary'], '+' => ['precedence' => 500, 'class' => 'WCML\\Twig\\Node\\Expression\\Unary\\PosUnary']], ['or' => ['precedence' => 10, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\OrBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'and' => ['precedence' => 15, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\AndBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'b-or' => ['precedence' => 16, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\BitwiseOrBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'b-xor' => ['precedence' => 17, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\BitwiseXorBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'b-and' => ['precedence' => 18, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\BitwiseAndBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '==' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\EqualBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '!=' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\NotEqualBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '<' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\LessBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '>' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\GreaterBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '>=' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\GreaterEqualBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '<=' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\LessEqualBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'not in' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\NotInBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'in' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\InBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'matches' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\MatchesBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'starts with' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\StartsWithBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'ends with' => ['precedence' => 20, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\EndsWithBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '..' => ['precedence' => 25, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\RangeBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '+' => ['precedence' => 30, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\AddBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '-' => ['precedence' => 30, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\SubBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '~' => ['precedence' => 40, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\ConcatBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '*' => ['precedence' => 60, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\MulBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '/' => ['precedence' => 60, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\DivBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '//' => ['precedence' => 60, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\FloorDivBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '%' => ['precedence' => 60, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\ModBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'is' => ['precedence' => 100, 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], 'is not' => ['precedence' => 100, 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_LEFT], '**' => ['precedence' => 200, 'class' => 'WCML\\Twig\\Node\\Expression\\Binary\\PowerBinary', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_RIGHT], '??' => ['precedence' => 300, 'class' => 'WCML\\Twig\\Node\\Expression\\NullCoalesceExpression', 'associativity' => \WCML\Twig\ExpressionParser::OPERATOR_RIGHT]]];
    }
    public function getName()
    {
        return 'core';
    }
}
\class_alias('WCML\\Twig\\Extension\\CoreExtension', 'WCML\\Twig_Extension_Core');
namespace WCML;

use WCML\Twig\Environment;
use WCML\Twig\Error\LoaderError;
use WCML\Twig\Error\RuntimeError;
use WCML\Twig\Loader\SourceContextLoaderInterface;
use WCML\Twig\Markup;
use WCML\Twig\Node\Expression\ConstantExpression;
use WCML\Twig\Node\Node;
/**
 * Cycles over a value.
 *
 * @param \ArrayAccess|array $values
 * @param int                $position The cycle position
 *
 * @return string The next value in the cycle
 */
function twig_cycle($values, $position)
{
    if (!\is_array($values) && !$values instanceof \ArrayAccess) {
        return $values;
    }
    return $values[$position % \count($values)];
}
/**
 * Returns a random value depending on the supplied parameter type:
 * - a random item from a \Traversable or array
 * - a random character from a string
 * - a random integer between 0 and the integer parameter.
 *
 * @param \Traversable|array|int|float|string $values The values to pick a random item from
 * @param int|null                            $max    Maximum value used when $values is an int
 *
 * @throws RuntimeError when $values is an empty array (does not apply to an empty string which is returned as is)
 *
 * @return mixed A random value from the given sequence
 */
function twig_random(\WCML\Twig\Environment $env, $values = null, $max = null)
{
    if (null === $values) {
        return null === $max ? \mt_rand() : \mt_rand(0, $max);
    }
    if (\is_int($values) || \is_float($values)) {
        if (null === $max) {
            if ($values < 0) {
                $max = 0;
                $min = $values;
            } else {
                $max = $values;
                $min = 0;
            }
        } else {
            $min = $values;
            $max = $max;
        }
        return \mt_rand($min, $max);
    }
    if (\is_string($values)) {
        if ('' === $values) {
            return '';
        }
        if (null !== ($charset = $env->getCharset())) {
            if ('UTF-8' !== $charset) {
                $values = \WCML\twig_convert_encoding($values, 'UTF-8', $charset);
            }
            // unicode version of str_split()
            // split at all positions, but not after the start and not before the end
            $values = \preg_split('/(?<!^)(?!$)/u', $values);
            if ('UTF-8' !== $charset) {
                foreach ($values as $i => $value) {
                    $values[$i] = \WCML\twig_convert_encoding($value, $charset, 'UTF-8');
                }
            }
        } else {
            return $values[\mt_rand(0, \strlen($values) - 1)];
        }
    }
    if (!\WCML\twig_test_iterable($values)) {
        return $values;
    }
    $values = \WCML\twig_to_array($values);
    if (0 === \count($values)) {
        throw new \WCML\Twig\Error\RuntimeError('The random function cannot pick from an empty array.');
    }
    return $values[\array_rand($values, 1)];
}
/**
 * Converts a date to the given format.
 *
 *   {{ post.published_at|date("m/d/Y") }}
 *
 * @param \DateTime|\DateTimeInterface|\DateInterval|string $date     A date
 * @param string|null                                       $format   The target format, null to use the default
 * @param \DateTimeZone|string|false|null                   $timezone The target timezone, null to use the default, false to leave unchanged
 *
 * @return string The formatted date
 */
function twig_date_format_filter(\WCML\Twig\Environment $env, $date, $format = null, $timezone = null)
{
    if (null === $format) {
        $formats = $env->getExtension('WCML\\Twig\\Extension\\CoreExtension')->getDateFormat();
        $format = $date instanceof \DateInterval ? $formats[1] : $formats[0];
    }
    if ($date instanceof \DateInterval) {
        return $date->format($format);
    }
    return \WCML\twig_date_converter($env, $date, $timezone)->format($format);
}
/**
 * Returns a new date object modified.
 *
 *   {{ post.published_at|date_modify("-1day")|date("m/d/Y") }}
 *
 * @param \DateTime|string $date     A date
 * @param string           $modifier A modifier string
 *
 * @return \DateTime
 */
function twig_date_modify_filter(\WCML\Twig\Environment $env, $date, $modifier)
{
    $date = \WCML\twig_date_converter($env, $date, \false);
    $resultDate = $date->modify($modifier);
    // This is a hack to ensure PHP 5.2 support and support for \DateTimeImmutable
    // \DateTime::modify does not return the modified \DateTime object < 5.3.0
    // and \DateTimeImmutable does not modify $date.
    return null === $resultDate ? $date : $resultDate;
}
/**
 * Converts an input to a \DateTime instance.
 *
 *    {% if date(user.created_at) < date('+2days') %}
 *      {# do something #}
 *    {% endif %}
 *
 * @param \DateTime|\DateTimeInterface|string|null $date     A date
 * @param \DateTimeZone|string|false|null          $timezone The target timezone, null to use the default, false to leave unchanged
 *
 * @return \DateTime
 */
function twig_date_converter(\WCML\Twig\Environment $env, $date = null, $timezone = null)
{
    // determine the timezone
    if (\false !== $timezone) {
        if (null === $timezone) {
            $timezone = $env->getExtension('WCML\\Twig\\Extension\\CoreExtension')->getTimezone();
        } elseif (!$timezone instanceof \DateTimeZone) {
            $timezone = new \DateTimeZone($timezone);
        }
    }
    // immutable dates
    if ($date instanceof \DateTimeImmutable) {
        return \false !== $timezone ? $date->setTimezone($timezone) : $date;
    }
    if ($date instanceof \DateTime || $date instanceof \DateTimeInterface) {
        $date = clone $date;
        if (\false !== $timezone) {
            $date->setTimezone($timezone);
        }
        return $date;
    }
    if (null === $date || 'now' === $date) {
        return new \DateTime($date, \false !== $timezone ? $timezone : $env->getExtension('WCML\\Twig\\Extension\\CoreExtension')->getTimezone());
    }
    $asString = (string) $date;
    if (\ctype_digit($asString) || !empty($asString) && '-' === $asString[0] && \ctype_digit(\substr($asString, 1))) {
        $date = new \DateTime('@' . $date);
    } else {
        $date = new \DateTime($date, $env->getExtension('WCML\\Twig\\Extension\\CoreExtension')->getTimezone());
    }
    if (\false !== $timezone) {
        $date->setTimezone($timezone);
    }
    return $date;
}
/**
 * Replaces strings within a string.
 *
 * @param string             $str  String to replace in
 * @param array|\Traversable $from Replace values
 * @param string|null        $to   Replace to, deprecated (@see https://secure.php.net/manual/en/function.strtr.php)
 *
 * @return string
 */
function twig_replace_filter($str, $from, $to = null)
{
    if (\is_string($from) && \is_string($to)) {
        @\trigger_error('Using "replace" with character by character replacement is deprecated since version 1.22 and will be removed in Twig 2.0', \E_USER_DEPRECATED);
        return \strtr($str, $from, $to);
    }
    if (!\WCML\twig_test_iterable($from)) {
        throw new \WCML\Twig\Error\RuntimeError(\sprintf('The "replace" filter expects an array or "Traversable" as replace values, got "%s".', \is_object($from) ? \get_class($from) : \gettype($from)));
    }
    return \strtr($str, \WCML\twig_to_array($from));
}
/**
 * Rounds a number.
 *
 * @param int|float $value     The value to round
 * @param int|float $precision The rounding precision
 * @param string    $method    The method to use for rounding
 *
 * @return int|float The rounded number
 */
function twig_round($value, $precision = 0, $method = 'common')
{
    if ('common' == $method) {
        return \round($value, $precision);
    }
    if ('ceil' != $method && 'floor' != $method) {
        throw new \WCML\Twig\Error\RuntimeError('The round filter only supports the "common", "ceil", and "floor" methods.');
    }
    return $method($value * \pow(10, $precision)) / \pow(10, $precision);
}
/**
 * Number format filter.
 *
 * All of the formatting options can be left null, in that case the defaults will
 * be used.  Supplying any of the parameters will override the defaults set in the
 * environment object.
 *
 * @param mixed  $number       A float/int/string of the number to format
 * @param int    $decimal      the number of decimal points to display
 * @param string $decimalPoint the character(s) to use for the decimal point
 * @param string $thousandSep  the character(s) to use for the thousands separator
 *
 * @return string The formatted number
 */
function twig_number_format_filter(\WCML\Twig\Environment $env, $number, $decimal = null, $decimalPoint = null, $thousandSep = null)
{
    $defaults = $env->getExtension('WCML\\Twig\\Extension\\CoreExtension')->getNumberFormat();
    if (null === $decimal) {
        $decimal = $defaults[0];
    }
    if (null === $decimalPoint) {
        $decimalPoint = $defaults[1];
    }
    if (null === $thousandSep) {
        $thousandSep = $defaults[2];
    }
    return \number_format((float) $number, $decimal, $decimalPoint, $thousandSep);
}
/**
 * URL encodes (RFC 3986) a string as a path segment or an array as a query string.
 *
 * @param string|array $url A URL or an array of query parameters
 *
 * @return string The URL encoded value
 */
function twig_urlencode_filter($url)
{
    if (\is_array($url)) {
        if (\defined('PHP_QUERY_RFC3986')) {
            return \http_build_query($url, '', '&', \PHP_QUERY_RFC3986);
        }
        return \http_build_query($url, '', '&');
    }
    return \rawurlencode($url);
}
/**
 * JSON encodes a variable.
 *
 * @param mixed $value   the value to encode
 * @param int   $options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT
 *
 * @return mixed The JSON encoded value
 */
function twig_jsonencode_filter($value, $options = 0)
{
    if ($value instanceof \WCML\Twig\Markup) {
        $value = (string) $value;
    } elseif (\is_array($value)) {
        \array_walk_recursive($value, '\\WCML\\_twig_markup2string');
    }
    return \json_encode($value, $options);
}
function _twig_markup2string(&$value)
{
    if ($value instanceof \WCML\Twig\Markup) {
        $value = (string) $value;
    }
}
/**
 * Merges an array with another one.
 *
 *  {% set items = { 'apple': 'fruit', 'orange': 'fruit' } %}
 *
 *  {% set items = items|merge({ 'peugeot': 'car' }) %}
 *
 *  {# items now contains { 'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'car' } #}
 *
 * @param array|\Traversable $arr1 An array
 * @param array|\Traversable $arr2 An array
 *
 * @return array The merged array
 */
function twig_array_merge($arr1, $arr2)
{
    if (!\WCML\twig_test_iterable($arr1)) {
        throw new \WCML\Twig\Error\RuntimeError(\sprintf('The merge filter only works with arrays or "Traversable", got "%s" as first argument.', \gettype($arr1)));
    }
    if (!\WCML\twig_test_iterable($arr2)) {
        throw new \WCML\Twig\Error\RuntimeError(\sprintf('The merge filter only works with arrays or "Traversable", got "%s" as second argument.', \gettype($arr2)));
    }
    return \array_merge(\WCML\twig_to_array($arr1), \WCML\twig_to_array($arr2));
}
/**
 * Slices a variable.
 *
 * @param mixed $item         A variable
 * @param int   $start        Start of the slice
 * @param int   $length       Size of the slice
 * @param bool  $preserveKeys Whether to preserve key or not (when the input is an array)
 *
 * @return mixed The sliced variable
 */
function twig_slice(\WCML\Twig\Environment $env, $item, $start, $length = null, $preserveKeys = \false)
{
    if ($item instanceof \Traversable) {
        while ($item instanceof \IteratorAggregate) {
            $item = $item->getIterator();
        }
        if ($start >= 0 && $length >= 0 && $item instanceof \Iterator) {
            try {
                return \iterator_to_array(new \LimitIterator($item, $start, null === $length ? -1 : $length), $preserveKeys);
            } catch (\OutOfBoundsException $e) {
                return [];
            }
        }
        $item = \iterator_to_array($item, $preserveKeys);
    }
    if (\is_array($item)) {
        return \array_slice($item, $start, $length, $preserveKeys);
    }
    $item = (string) $item;
    if (\function_exists('mb_get_info') && null !== ($charset = $env->getCharset())) {
        return (string) \mb_substr($item, $start, null === $length ? \mb_strlen($item, $charset) - $start : $length, $charset);
    }
    return (string) (null === $length ? \substr($item, $start) : \substr($item, $start, $length));
}
/**
 * Returns the first element of the item.
 *
 * @param mixed $item A variable
 *
 * @return mixed The first element of the item
 */
function twig_first(\WCML\Twig\Environment $env, $item)
{
    $elements = \WCML\twig_slice($env, $item, 0, 1, \false);
    return \is_string($elements) ? $elements : \current($elements);
}
/**
 * Returns the last element of the item.
 *
 * @param mixed $item A variable
 *
 * @return mixed The last element of the item
 */
function twig_last(\WCML\Twig\Environment $env, $item)
{
    $elements = \WCML\twig_slice($env, $item, -1, 1, \false);
    return \is_string($elements) ? $elements : \current($elements);
}
/**
 * Joins the values to a string.
 *
 * The separators between elements are empty strings per default, you can define them with the optional parameters.
 *
 *  {{ [1, 2, 3]|join(', ', ' and ') }}
 *  {# returns 1, 2 and 3 #}
 *
 *  {{ [1, 2, 3]|join('|') }}
 *  {# returns 1|2|3 #}
 *
 *  {{ [1, 2, 3]|join }}
 *  {# returns 123 #}
 *
 * @param array       $value An array
 * @param string      $glue  The separator
 * @param string|null $and   The separator for the last pair
 *
 * @return string The concatenated string
 */
function twig_join_filter($value, $glue = '', $and = null)
{
    if (!\WCML\twig_test_iterable($value)) {
        $value = (array) $value;
    }
    $value = \WCML\twig_to_array($value, \false);
    if (0 === \count($value)) {
        return '';
    }
    if (null === $and || $and === $glue) {
        return \implode($glue, $value);
    }
    if (1 === \count($value)) {
        return $value[0];
    }
    return \implode($glue, \array_slice($value, 0, -1)) . $and . $value[\count($value) - 1];
}
/**
 * Splits the string into an array.
 *
 *  {{ "one,two,three"|split(',') }}
 *  {# returns [one, two, three] #}
 *
 *  {{ "one,two,three,four,five"|split(',', 3) }}
 *  {# returns [one, two, "three,four,five"] #}
 *
 *  {{ "123"|split('') }}
 *  {# returns [1, 2, 3] #}
 *
 *  {{ "aabbcc"|split('', 2) }}
 *  {# returns [aa, bb, cc] #}
 *
 * @param string $value     A string
 * @param string $delimiter The delimiter
 * @param int    $limit     The limit
 *
 * @return array The split string as an array
 */
function twig_split_filter(\WCML\Twig\Environment $env, $value, $delimiter, $limit = null)
{
    if (!empty($delimiter)) {
        return null === $limit ? \explode($delimiter, $value) : \explode($delimiter, $value, $limit);
    }
    if (!\function_exists('mb_get_info') || null === ($charset = $env->getCharset())) {
        return \str_split($value, null === $limit ? 1 : $limit);
    }
    if ($limit <= 1) {
        return \preg_split('/(?<!^)(?!$)/u', $value);
    }
    $length = \mb_strlen($value, $charset);
    if ($length < $limit) {
        return [$value];
    }
    $r = [];
    for ($i = 0; $i < $length; $i += $limit) {
        $r[] = \mb_substr($value, $i, $limit, $charset);
    }
    return $r;
}
// The '_default' filter is used internally to avoid using the ternary operator
// which costs a lot for big contexts (before PHP 5.4). So, on average,
// a function call is cheaper.
/**
 * @internal
 */
function _twig_default_filter($value, $default = '')
{
    if (\WCML\twig_test_empty($value)) {
        return $default;
    }
    return $value;
}
/**
 * Returns the keys for the given array.
 *
 * It is useful when you want to iterate over the keys of an array:
 *
 *  {% for key in array|keys %}
 *      {# ... #}
 *  {% endfor %}
 *
 * @param array $array An array
 *
 * @return array The keys
 */
function twig_get_array_keys_filter($array)
{
    if ($array instanceof \Traversable) {
        while ($array instanceof \IteratorAggregate) {
            $array = $array->getIterator();
        }
        if ($array instanceof \Iterator) {
            $keys = [];
            $array->rewind();
            while ($array->valid()) {
                $keys[] = $array->key();
                $array->next();
            }
            return $keys;
        }
        $keys = [];
        foreach ($array as $key => $item) {
            $keys[] = $key;
        }
        return $keys;
    }
    if (!\is_array($array)) {
        return [];
    }
    return \array_keys($array);
}
/**
 * Reverses a variable.
 *
 * @param array|\Traversable|string $item         An array, a \Traversable instance, or a string
 * @param bool                      $preserveKeys Whether to preserve key or not
 *
 * @return mixed The reversed input
 */
function twig_reverse_filter(\WCML\Twig\Environment $env, $item, $preserveKeys = \false)
{
    if ($item instanceof \Traversable) {
        return \array_reverse(\iterator_to_array($item), $preserveKeys);
    }
    if (\is_array($item)) {
        return \array_reverse($item, $preserveKeys);
    }
    if (null !== ($charset = $env->getCharset())) {
        $string = (string) $item;
        if ('UTF-8' !== $charset) {
            $item = \WCML\twig_convert_encoding($string, 'UTF-8', $charset);
        }
        \preg_match_all('/./us', $item, $matches);
        $string = \implode('', \array_reverse($matches[0]));
        if ('UTF-8' !== $charset) {
            $string = \WCML\twig_convert_encoding($string, $charset, 'UTF-8');
        }
        return $string;
    }
    return \strrev((string) $item);
}
/**
 * Sorts an array.
 *
 * @param array|\Traversable $array
 *
 * @return array
 */
function twig_sort_filter($array)
{
    if ($array instanceof \Traversable) {
        $array = \iterator_to_array($array);
    } elseif (!\is_array($array)) {
        throw new \WCML\Twig\Error\RuntimeError(\sprintf('The sort filter only works with arrays or "Traversable", got "%s".', \gettype($array)));
    }
    \asort($array);
    return $array;
}
/**
 * @internal
 */
function twig_in_filter($value, $compare)
{
    if ($value instanceof \WCML\Twig\Markup) {
        $value = (string) $value;
    }
    if ($compare instanceof \WCML\Twig\Markup) {
        $compare = (string) $compare;
    }
    if (\is_array($compare)) {
        return \in_array($value, $compare, \is_object($value) || \is_resource($value));
    } elseif (\is_string($compare) && (\is_string($value) || \is_int($value) || \is_float($value))) {
        return '' === $value || \false !== \strpos($compare, (string) $value);
    } elseif ($compare instanceof \Traversable) {
        if (\is_object($value) || \is_resource($value)) {
            foreach ($compare as $item) {
                if ($item === $value) {
                    return \true;
                }
            }
        } else {
            foreach ($compare as $item) {
                if ($item == $value) {
                    return \true;
                }
            }
        }
        return \false;
    }
    return \false;
}
/**
 * Returns a trimmed string.
 *
 * @return string
 *
 * @throws RuntimeError When an invalid trimming side is used (not a string or not 'left', 'right', or 'both')
 */
function twig_trim_filter($string, $characterMask = null, $side = 'both')
{
    if (null === $characterMask) {
        $characterMask = " \t\n\r\0\v";
    }
    switch ($side) {
        case 'both':
            return \trim($string, $characterMask);
        case 'left':
            return \ltrim($string, $characterMask);
        case 'right':
            return \rtrim($string, $characterMask);
        default:
            throw new \WCML\Twig\Error\RuntimeError('Trimming side must be "left", "right" or "both".');
    }
}
/**
 * Removes whitespaces between HTML tags.
 *
 * @return string
 */
function twig_spaceless($content)
{
    return \trim(\preg_replace('/>\\s+</', '><', $content));
}
/**
 * Escapes a string.
 *
 * @param mixed  $string     The value to be escaped
 * @param string $strategy   The escaping strategy
 * @param string $charset    The charset
 * @param bool   $autoescape Whether the function is called by the auto-escaping feature (true) or by the developer (false)
 *
 * @return string
 */
function twig_escape_filter(\WCML\Twig\Environment $env, $string, $strategy = 'html', $charset = null, $autoescape = \false)
{
    if ($autoescape && $string instanceof \WCML\Twig\Markup) {
        return $string;
    }
    if (!\is_string($string)) {
        if (\is_object($string) && \method_exists($string, '__toString')) {
            $string = (string) $string;
        } elseif (\in_array($strategy, ['html', 'js', 'css', 'html_attr', 'url'])) {
            return $string;
        }
    }
    if ('' === $string) {
        return '';
    }
    if (null === $charset) {
        $charset = $env->getCharset();
    }
    switch ($strategy) {
        case 'html':
            // see https://secure.php.net/htmlspecialchars
            // Using a static variable to avoid initializing the array
            // each time the function is called. Moving the declaration on the
            // top of the function slow downs other escaping strategies.
            static $htmlspecialcharsCharsets = ['ISO-8859-1' => \true, 'ISO8859-1' => \true, 'ISO-8859-15' => \true, 'ISO8859-15' => \true, 'utf-8' => \true, 'UTF-8' => \true, 'CP866' => \true, 'IBM866' => \true, '866' => \true, 'CP1251' => \true, 'WINDOWS-1251' => \true, 'WIN-1251' => \true, '1251' => \true, 'CP1252' => \true, 'WINDOWS-1252' => \true, '1252' => \true, 'KOI8-R' => \true, 'KOI8-RU' => \true, 'KOI8R' => \true, 'BIG5' => \true, '950' => \true, 'GB2312' => \true, '936' => \true, 'BIG5-HKSCS' => \true, 'SHIFT_JIS' => \true, 'SJIS' => \true, '932' => \true, 'EUC-JP' => \true, 'EUCJP' => \true, 'ISO8859-5' => \true, 'ISO-8859-5' => \true, 'MACROMAN' => \true];
            if (isset($htmlspecialcharsCharsets[$charset])) {
                return \htmlspecialchars($string, \ENT_QUOTES | \ENT_SUBSTITUTE, $charset);
            }
            if (isset($htmlspecialcharsCharsets[\strtoupper($charset)])) {
                // cache the lowercase variant for future iterations
                $htmlspecialcharsCharsets[$charset] = \true;
                return \htmlspecialchars($string, \ENT_QUOTES | \ENT_SUBSTITUTE, $charset);
            }
            $string = \WCML\twig_convert_encoding($string, 'UTF-8', $charset);
            $string = \htmlspecialchars($string, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
            return \WCML\twig_convert_encoding($string, $charset, 'UTF-8');
        case 'js':
            // escape all non-alphanumeric characters
            // into their \x or \uHHHH representations
            if ('UTF-8' !== $charset) {
                $string = \WCML\twig_convert_encoding($string, 'UTF-8', $charset);
            }
            if (!\preg_match('//u', $string)) {
                throw new \WCML\Twig\Error\RuntimeError('The string to escape is not a valid UTF-8 string.');
            }
            $string = \preg_replace_callback('#[^a-zA-Z0-9,\\._]#Su', '\\WCML\\_twig_escape_js_callback', $string);
            if ('UTF-8' !== $charset) {
                $string = \WCML\twig_convert_encoding($string, $charset, 'UTF-8');
            }
            return $string;
        case 'css':
            if ('UTF-8' !== $charset) {
                $string = \WCML\twig_convert_encoding($string, 'UTF-8', $charset);
            }
            if (!\preg_match('//u', $string)) {
                throw new \WCML\Twig\Error\RuntimeError('The string to escape is not a valid UTF-8 string.');
            }
            $string = \preg_replace_callback('#[^a-zA-Z0-9]#Su', '\\WCML\\_twig_escape_css_callback', $string);
            if ('UTF-8' !== $charset) {
                $string = \WCML\twig_convert_encoding($string, $charset, 'UTF-8');
            }
            return $string;
        case 'html_attr':
            if ('UTF-8' !== $charset) {
                $string = \WCML\twig_convert_encoding($string, 'UTF-8', $charset);
            }
            if (!\preg_match('//u', $string)) {
                throw new \WCML\Twig\Error\RuntimeError('The string to escape is not a valid UTF-8 string.');
            }
            $string = \preg_replace_callback('#[^a-zA-Z0-9,\\.\\-_]#Su', '\\WCML\\_twig_escape_html_attr_callback', $string);
            if ('UTF-8' !== $charset) {
                $string = \WCML\twig_convert_encoding($string, $charset, 'UTF-8');
            }
            return $string;
        case 'url':
            return \rawurlencode($string);
        default:
            static $escapers;
            if (null === $escapers) {
                $escapers = $env->getExtension('WCML\\Twig\\Extension\\CoreExtension')->getEscapers();
            }
            if (isset($escapers[$strategy])) {
                return \call_user_func($escapers[$strategy], $env, $string, $charset);
            }
            $validStrategies = \implode(', ', \array_merge(['html', 'js', 'url', 'css', 'html_attr'], \array_keys($escapers)));
            throw new \WCML\Twig\Error\RuntimeError(\sprintf('Invalid escaping strategy "%s" (valid ones: %s).', $strategy, $validStrategies));
    }
}
/**
 * @internal
 */
function twig_escape_filter_is_safe(\WCML\Twig\Node\Node $filterArgs)
{
    foreach ($filterArgs as $arg) {
        if ($arg instanceof \WCML\Twig\Node\Expression\ConstantExpression) {
            return [$arg->getAttribute('value')];
        }
        return [];
    }
    return ['html'];
}
if (\function_exists('mb_convert_encoding')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return \mb_convert_encoding($string, $to, $from);
    }
} elseif (\function_exists('iconv')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return \iconv($from, $to, $string);
    }
} else {
    function twig_convert_encoding($string, $to, $from)
    {
        throw new \WCML\Twig\Error\RuntimeError('No suitable convert encoding function (use UTF-8 as your encoding or install the iconv or mbstring extension).');
    }
}
if (\function_exists('mb_ord')) {
    function twig_ord($string)
    {
        return \mb_ord($string, 'UTF-8');
    }
} else {
    function twig_ord($string)
    {
        $code = ($string = \unpack('C*', \substr($string, 0, 4))) ? $string[1] : 0;
        if (0xf0 <= $code) {
            return ($code - 0xf0 << 18) + ($string[2] - 0x80 << 12) + ($string[3] - 0x80 << 6) + $string[4] - 0x80;
        }
        if (0xe0 <= $code) {
            return ($code - 0xe0 << 12) + ($string[2] - 0x80 << 6) + $string[3] - 0x80;
        }
        if (0xc0 <= $code) {
            return ($code - 0xc0 << 6) + $string[2] - 0x80;
        }
        return $code;
    }
}
function _twig_escape_js_callback($matches)
{
    $char = $matches[0];
    /*
     * A few characters have short escape sequences in JSON and JavaScript.
     * Escape sequences supported only by JavaScript, not JSON, are ommitted.
     * \" is also supported but omitted, because the resulting string is not HTML safe.
     */
    static $shortMap = ['\\' => '\\\\', '/' => '\\/', "\10" => '\\b', "\f" => '\\f', "\n" => '\\n', "\r" => '\\r', "\t" => '\\t'];
    if (isset($shortMap[$char])) {
        return $shortMap[$char];
    }
    // \uHHHH
    $char = \WCML\twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');
    $char = \strtoupper(\bin2hex($char));
    if (4 >= \strlen($char)) {
        return \sprintf('\\u%04s', $char);
    }
    return \sprintf('\\u%04s\\u%04s', \substr($char, 0, -4), \substr($char, -4));
}
function _twig_escape_css_callback($matches)
{
    $char = $matches[0];
    return \sprintf('\\%X ', 1 === \strlen($char) ? \ord($char) : \WCML\twig_ord($char));
}
/**
 * This function is adapted from code coming from Zend Framework.
 *
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://framework.zend.com/license/new-bsd New BSD License
 */
function _twig_escape_html_attr_callback($matches)
{
    $chr = $matches[0];
    $ord = \ord($chr);
    /*
     * The following replaces characters undefined in HTML with the
     * hex entity for the Unicode replacement character.
     */
    if ($ord <= 0x1f && "\t" != $chr && "\n" != $chr && "\r" != $chr || $ord >= 0x7f && $ord <= 0x9f) {
        return '&#xFFFD;';
    }
    /*
     * Check if the current character to escape has a name entity we should
     * replace it with while grabbing the hex value of the character.
     */
    if (1 == \strlen($chr)) {
        /*
         * While HTML supports far more named entities, the lowest common denominator
         * has become HTML5's XML Serialisation which is restricted to the those named
         * entities that XML supports. Using HTML entities would result in this error:
         *     XML Parsing Error: undefined entity
         */
        static $entityMap = [
            34 => '&quot;',
            /* quotation mark */
            38 => '&amp;',
            /* ampersand */
            60 => '&lt;',
            /* less-than sign */
            62 => '&gt;',
        ];
        if (isset($entityMap[$ord])) {
            return $entityMap[$ord];
        }
        return \sprintf('&#x%02X;', $ord);
    }
    /*
     * Per OWASP recommendations, we'll use hex entities for any other
     * characters where a named entity does not exist.
     */
    return \sprintf('&#x%04X;', \WCML\twig_ord($chr));
}
// add multibyte extensions if possible
if (\function_exists('mb_get_info')) {
    /**
     * Returns the length of a variable.
     *
     * @param mixed $thing A variable
     *
     * @return int The length of the value
     */
    function twig_length_filter(\WCML\Twig\Environment $env, $thing)
    {
        if (null === $thing) {
            return 0;
        }
        if (\is_scalar($thing)) {
            return \mb_strlen($thing, $env->getCharset());
        }
        if ($thing instanceof \Countable || \is_array($thing) || $thing instanceof \SimpleXMLElement) {
            return \count($thing);
        }
        if ($thing instanceof \Traversable) {
            return \iterator_count($thing);
        }
        if (\is_object($thing) && \method_exists($thing, '__toString')) {
            return \mb_strlen((string) $thing, $env->getCharset());
        }
        return 1;
    }
    /**
     * Converts a string to uppercase.
     *
     * @param string $string A string
     *
     * @return string The uppercased string
     */
    function twig_upper_filter(\WCML\Twig\Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return \mb_strtoupper($string, $charset);
        }
        return \strtoupper($string);
    }
    /**
     * Converts a string to lowercase.
     *
     * @param string $string A string
     *
     * @return string The lowercased string
     */
    function twig_lower_filter(\WCML\Twig\Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return \mb_strtolower($string, $charset);
        }
        return \strtolower($string);
    }
    /**
     * Returns a titlecased string.
     *
     * @param string $string A string
     *
     * @return string The titlecased string
     */
    function twig_title_string_filter(\WCML\Twig\Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return \mb_convert_case($string, \MB_CASE_TITLE, $charset);
        }
        return \ucwords(\strtolower($string));
    }
    /**
     * Returns a capitalized string.
     *
     * @param string $string A string
     *
     * @return string The capitalized string
     */
    function twig_capitalize_string_filter(\WCML\Twig\Environment $env, $string)
    {
        if (null !== ($charset = $env->getCharset())) {
            return \mb_strtoupper(\mb_substr($string, 0, 1, $charset), $charset) . \mb_strtolower(\mb_substr($string, 1, \mb_strlen($string, $charset), $charset), $charset);
        }
        return \ucfirst(\strtolower($string));
    }
} else {
    /**
     * Returns the length of a variable.
     *
     * @param mixed $thing A variable
     *
     * @return int The length of the value
     */
    function twig_length_filter(\WCML\Twig\Environment $env, $thing)
    {
        if (null === $thing) {
            return 0;
        }
        if (\is_scalar($thing)) {
            return \strlen($thing);
        }
        if ($thing instanceof \SimpleXMLElement) {
            return \count($thing);
        }
        if (\is_object($thing) && \method_exists($thing, '__toString') && !$thing instanceof \Countable) {
            return \strlen((string) $thing);
        }
        if ($thing instanceof \Countable || \is_array($thing)) {
            return \count($thing);
        }
        if ($thing instanceof \IteratorAggregate) {
            return \iterator_count($thing);
        }
        return 1;
    }
    /**
     * Returns a titlecased string.
     *
     * @param string $string A string
     *
     * @return string The titlecased string
     */
    function twig_title_string_filter(\WCML\Twig\Environment $env, $string)
    {
        return \ucwords(\strtolower($string));
    }
    /**
     * Returns a capitalized string.
     *
     * @param string $string A string
     *
     * @return string The capitalized string
     */
    function twig_capitalize_string_filter(\WCML\Twig\Environment $env, $string)
    {
        return \ucfirst(\strtolower($string));
    }
}
/**
 * @internal
 */
function twig_ensure_traversable($seq)
{
    if ($seq instanceof \Traversable || \is_array($seq)) {
        return $seq;
    }
    return [];
}
/**
 * @internal
 */
function twig_to_array($seq, $preserveKeys = \true)
{
    if ($seq instanceof \Traversable) {
        return \iterator_to_array($seq, $preserveKeys);
    }
    if (!\is_array($seq)) {
        return $seq;
    }
    return $preserveKeys ? $seq : \array_values($seq);
}
/**
 * Checks if a variable is empty.
 *
 *    {# evaluates to true if the foo variable is null, false, or the empty string #}
 *    {% if foo is empty %}
 *        {# ... #}
 *    {% endif %}
 *
 * @param mixed $value A variable
 *
 * @return bool true if the value is empty, false otherwise
 */
function twig_test_empty($value)
{
    if ($value instanceof \Countable) {
        return 0 == \count($value);
    }
    if (\is_object($value) && \method_exists($value, '__toString')) {
        return '' === (string) $value;
    }
    return '' === $value || \false === $value || null === $value || [] === $value;
}
/**
 * Checks if a variable is traversable.
 *
 *    {# evaluates to true if the foo variable is an array or a traversable object #}
 *    {% if foo is iterable %}
 *        {# ... #}
 *    {% endif %}
 *
 * @param mixed $value A variable
 *
 * @return bool true if the value is traversable
 */
function twig_test_iterable($value)
{
    return $value instanceof \Traversable || \is_array($value);
}
/**
 * Renders a template.
 *
 * @param array        $context
 * @param string|array $template      The template to render or an array of templates to try consecutively
 * @param array        $variables     The variables to pass to the template
 * @param bool         $withContext
 * @param bool         $ignoreMissing Whether to ignore missing templates or not
 * @param bool         $sandboxed     Whether to sandbox the template or not
 *
 * @return string The rendered template
 */
function twig_include(\WCML\Twig\Environment $env, $context, $template, $variables = [], $withContext = \true, $ignoreMissing = \false, $sandboxed = \false)
{
    $alreadySandboxed = \false;
    $sandbox = null;
    if ($withContext) {
        $variables = \array_merge($context, $variables);
    }
    if ($isSandboxed = $sandboxed && $env->hasExtension('WCML\\Twig\\Extension\\SandboxExtension')) {
        $sandbox = $env->getExtension('WCML\\Twig\\Extension\\SandboxExtension');
        if (!($alreadySandboxed = $sandbox->isSandboxed())) {
            $sandbox->enableSandbox();
        }
    }
    $loaded = null;
    try {
        $loaded = $env->resolveTemplate($template);
    } catch (\WCML\Twig\Error\LoaderError $e) {
        if (!$ignoreMissing) {
            if ($isSandboxed && !$alreadySandboxed) {
                $sandbox->disableSandbox();
            }
            throw $e;
        }
    } catch (\Throwable $e) {
        if ($isSandboxed && !$alreadySandboxed) {
            $sandbox->disableSandbox();
        }
        throw $e;
    } catch (\Exception $e) {
        if ($isSandboxed && !$alreadySandboxed) {
            $sandbox->disableSandbox();
        }
        throw $e;
    }
    try {
        $ret = $loaded ? $loaded->render($variables) : '';
    } catch (\Exception $e) {
        if ($isSandboxed && !$alreadySandboxed) {
            $sandbox->disableSandbox();
        }
        throw $e;
    }
    if ($isSandboxed && !$alreadySandboxed) {
        $sandbox->disableSandbox();
    }
    return $ret;
}
/**
 * Returns a template content without rendering it.
 *
 * @param string $name          The template name
 * @param bool   $ignoreMissing Whether to ignore missing templates or not
 *
 * @return string The template source
 */
function twig_source(\WCML\Twig\Environment $env, $name, $ignoreMissing = \false)
{
    $loader = $env->getLoader();
    try {
        if (!$loader instanceof \WCML\Twig\Loader\SourceContextLoaderInterface) {
            return $loader->getSource($name);
        } else {
            return $loader->getSourceContext($name)->getCode();
        }
    } catch (\WCML\Twig\Error\LoaderError $e) {
        if (!$ignoreMissing) {
            throw $e;
        }
    }
}
/**
 * Provides the ability to get constants from instances as well as class/global constants.
 *
 * @param string      $constant The name of the constant
 * @param object|null $object   The object to get the constant from
 *
 * @return string
 */
function twig_constant($constant, $object = null)
{
    if (null !== $object) {
        $constant = \get_class($object) . '::' . $constant;
    }
    return \constant($constant);
}
/**
 * Checks if a constant exists.
 *
 * @param string      $constant The name of the constant
 * @param object|null $object   The object to get the constant from
 *
 * @return bool
 */
function twig_constant_is_defined($constant, $object = null)
{
    if (null !== $object) {
        $constant = \get_class($object) . '::' . $constant;
    }
    return \defined($constant);
}
/**
 * Batches item.
 *
 * @param array $items An array of items
 * @param int   $size  The size of the batch
 * @param mixed $fill  A value used to fill missing items
 *
 * @return array
 */
function twig_array_batch($items, $size, $fill = null, $preserveKeys = \true)
{
    if (!\WCML\twig_test_iterable($items)) {
        throw new \WCML\Twig\Error\RuntimeError(\sprintf('The "batch" filter expects an array or "Traversable", got "%s".', \is_object($items) ? \get_class($items) : \gettype($items)));
    }
    $size = \ceil($size);
    $result = \array_chunk(\WCML\twig_to_array($items, $preserveKeys), $size, $preserveKeys);
    if (null !== $fill && $result) {
        $last = \count($result) - 1;
        if ($fillCount = $size - \count($result[$last])) {
            for ($i = 0; $i < $fillCount; ++$i) {
                $result[$last][] = $fill;
            }
        }
    }
    return $result;
}
function twig_array_filter($array, $arrow)
{
    if (\is_array($array)) {
        if (\PHP_VERSION_ID >= 50600) {
            return \array_filter($array, $arrow, \ARRAY_FILTER_USE_BOTH);
        }
        return \array_filter($array, $arrow);
    }
    // the IteratorIterator wrapping is needed as some internal PHP classes are \Traversable but do not implement \Iterator
    return new \CallbackFilterIterator(new \IteratorIterator($array), $arrow);
}
function twig_array_map($array, $arrow)
{
    $r = [];
    foreach ($array as $k => $v) {
        $r[$k] = $arrow($v, $k);
    }
    return $r;
}
function twig_array_reduce($array, $arrow, $initial = null)
{
    if (!\is_array($array)) {
        $array = \iterator_to_array($array);
    }
    return \array_reduce($array, $arrow, $initial);
}
