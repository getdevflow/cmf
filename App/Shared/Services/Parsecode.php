<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Exception;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Inheritance\StaticProxyAware;
use ReflectionException;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_map;
use function call_user_func;
use function is_array;
use function is_callable;
use function join;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_replace_callback;
use function preg_split;
use function Qubus\Security\Helpers\t__;
use function sprintf;
use function str_replace;
use function stripcslashes;
use function strpos;
use function strtolower;
use function substr;
use function trim;

use const PREG_SET_ORDER;

final class Parsecode
{
    use StaticProxyAware;
    /**
     * Container for storing parsecode tags and their hook to call for the parsecode
     *
     * @access public
     * @var array
     */
    public static array $parsecodeTags = [];

    public function cleanPre(mixed $matches): array|string
    {
        if (is_array($matches)) {
            $text = $matches[1] . $matches[2] . "</pre>";
        } else {
            $text = $matches;
        }

        $text = str_replace('<br />', '', $text);
        $text = str_replace('<p>', "\n", $text);
        $text = str_replace('</p>', '', $text);

        return $text;
    }

    /**
     * Add hook for parsecode tag.
     *
     * <p>
     * <br />
     * There can only be one hook for each parsecode. Which means that if another
     * plugin has a similar parsecode, it will override yours or yours will override
     * theirs depending on which order the plugins are included and/or ran.
     * <br />
     * <br />
     * </p>
     *
     * Simplest example of a parsecode tag using the API:
     *
     *        <code>
     *            // [footag foo="bar"]
     *            function footag_func($atts) {
     *                return "foo = {$atts[foo]}";
     *            }
     *            Parsecode::add('footag', 'footag_func');
     *        </code>
     *
     * Example with nice attribute defaults:
     *
     *        <code>
     *            // [bartag foo="bar"]
     *            function bartag_func($atts) {
     *                $args = Parsecode::atts([
     *                'foo' => 'no foo',
     *                'baz' => 'default baz',
     *            ], $atts);
     *
     *            return "foo = {$args['foo']}";
     *            }
     *            Parsecode::add('bartag', 'bartag_func');
     *        </code>
     *
     * Example with enclosed content:
     *
     *        <code>
     *            // [baztag]content[/baztag]
     *            function baztag_func($atts, $content='') {
     *                return "content = $content";
     *            }
     *            Parsecode::add('baztag', 'baztag_func');
     *        </code>
     *
     * @param string $tag <p>Parsecode tag to be searched in content content.</p>
     * @param callable $func <p>Hook to run when parsecode is found.</p>
     * @return bool
     * @throws Exception
     */
    public function add(string $tag, callable $func): bool
    {
        if ('' === trim($tag)) {
            throw new Exception(t__(msgid: 'Invalid parsecode name: empty name given.', domain: 'devflow'));
        }

        if (0 !== preg_match('@[<>&/\[\]\x00-\x20]@', $tag)) {
            throw new Exception(
                sprintf(
                    t__(
                        msgid: 'Invalid parsecode name: %s. Do not use spaces or reserved characters: & / < > [ ]',
                        domain: 'devflow'
                    ),
                    $tag
                )
            );
        }

        if (is_callable($func)) {
            self::$parsecodeTags[$tag] = $func;

            return true;
        }
        return false;
    }

    /**
     * Removes hook for parsecode.
     *
     * @param string $tag parsecode tag to remove hook for.
     * @throws Exception
     */
    public function remove(string $tag): bool
    {
        if ('' === trim($tag)) {
            throw new Exception(t__(msgid: 'Invalid parsecode name: empty name given.', domain: 'devflow'));
        }

        if (isset(self::$parsecodeTags[$tag])) {
            unset(self::$parsecodeTags[$tag]);
            return true;
        }
        return false;
    }

    /**
     * Clear all parsecodes.
     *
     * This function is simple, it clears all the parsecode tags by replacing the
     * parsecodes global by a empty array. This is actually a very efficient method
     * for removing all parsecodes.
     */
    public function removeAll(): true
    {
        self::$parsecodeTags = [];
        return true;
    }

    /**
     * Whether a registered parsecode exists named $tag
     *
     * @param string $tag
     * @return bool
     */
    public function exists(string $tag): bool
    {
        return array_key_exists($tag, self::$parsecodeTags);
    }

    /**
     * Whether the passed content contains the specified parsecode.
     *
     * @param string $content
     * @param string $tag
     * @return bool
     */
    public function has(string $content, string $tag): bool
    {
        if (false === strpos($content, '[')) {
            return false;
        }
        if ($this->exists($tag)) {
            preg_match_all('/' . $this->getRegex() . '/s', $content, $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                return false;
            }
            foreach ($matches as $parsecode) {
                if ($tag === $parsecode[2]) {
                    return true;
                }
                if (!empty($parsecode[5]) && $this->has($parsecode[5], $tag)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Search content for parsecodes and filter parsecodes through their hooks.
     *
     * If there are no parsecode tags defined, then the content will be returned
     * without any filtering. This might cause issues when plugins are disabled but
     * the parsecode will still show up in the content or content.
     *
     * @param string $content Content to search for parsecodes
     * @return string Content with parsecodes filtered out.
     */
    public function doParsecode(string $content): string
    {
        if (false === strpos($content, '[')) {
            return $content;
        }

        if (empty(self::$parsecodeTags) || !is_array(self::$parsecodeTags)) {
            return $content;
        }

        // Find all registered tag names in $content.
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect(array_keys(self::$parsecodeTags), $matches[1]);

        if (empty($tagnames)) {
            return $content;
        }

        $pattern = $this->getRegex($tagnames);
        return preg_replace_callback("/$pattern/s", [$this, 'doTag'], $content);
    }

    /**
     * Retrieve the parsecode regular expression for searching.
     *
     * The regular expression combines the parsecode tags in the regular expression
     * in a regex class.
     *
     * The regular expression contains 6 different sub matches to help with parsing.
     *
     * 1 - An extra [ to allow for escaping parsecodes with double [[]]
     * 2 - The parsecode name
     * 3 - The parsecode argument list
     * 4 - The self-closing /
     * 5 - The content of a parsecode when it wraps some content.
     * 6 - An extra ] to allow for escaping parsecodes with double [[]]
     *
     * @param array|null $tagnames Optional. List of parsecodes to find. Defaults to all registered parsecodes.
     * @return string The parsecode search regular expression
     */
    public function getRegex(array $tagnames = null): string
    {
        if (empty($tagnames)) {
            $tagnames = array_keys(self::$parsecodeTags);
        }

        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        // WARNING! Do not change this regex without changing doTag() and strip_parsecode_tag()
        return
                '\\['                              // Opening bracket
                . '(\\[?)'                           // 1: Optional second opening bracket for escaping parsecodes: [[tag]]
                . "($tagregexp)"                     // 2: parsecode name
                . '\\b'                              // Word boundary
                . '('                                // 3: Unroll the loop: Inside the opening parsecode tag
                . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
                . '(?:'
                . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
                . '[^\\]\\/]*'               // Not a closing bracket or forward slash
                . ')*?'
                . ')'
                . '(?:'
                . '(\\/)'                        // 4: Self closing tag ...
                . '\\]'                          // ... and closing bracket
                . '|'
                . '\\]'                          // Closing bracket
                . '(?:'
                . '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing parsecode tags
                . '[^\\[]*+'             // Not an opening bracket
                . '(?:'
                . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing parsecode tag
                . '[^\\[]*+'         // Not an opening bracket
                . ')*+'
                . ')'
                . '\\[\\/\\2\\]'             // Closing parsecode tag
                . ')?'
                . ')'
                . '(\\]?)';                          // 6: Optional second closing brocket for escaping parsecodes: [[tag]]
    }

    /**
     * Regular Expression callable for $this->doParsecode() for calling parsecode hook.
     * 
     * @see $this->getRegex for details of the match array contents.
     * @access private
     * @param array $m Regular expression match array
     * @return mixed False on failure.
     */
    private function doTag(array $m): mixed
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        $tag = $m[2];
        $attr = $this->parseAtts($m[3]);

        if (isset($m[5])) {
            // enclosing tag - extra parameter
            return $m[1] . call_user_func(self::$parsecodeTags[$tag], $attr, $m[5], $tag) . $m[6];
        } else {
            // self-closing tag
            return $m[1] . call_user_func(self::$parsecodeTags[$tag], $attr, null, $tag) . $m[6];
        }
    }

    /**
     * Retrieve all attributes from the parsecodes tag.
     *
     * The attributes list has the attribute name as the key and the value of the
     * attribute as the value in the key/value pair. This allows for easier
     * retrieval of the attributes, since all attributes have to be known.
     *
     * @param string $text
     * @return string|array List of attributes and their value.
     */
    public function parseAtts(string $text): array|string
    {
        $atts = [];
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1])) {
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                } elseif (!empty($m[3])) {
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                } elseif (!empty($m[5])) {
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                } elseif (isset($m[7]) and strlen($m[7])) {
                    $atts[] = stripcslashes($m[7]);
                } elseif (isset($m[8])) {
                    $atts[] = stripcslashes($m[8]);
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    /**
     * Combine user attributes with known attributes and fill in defaults when needed.
     *
     * The pairs should be considered to be all the attributes which are
     * supported by the caller and given as a list. The returned attributes will
     * only contain the attributes in the $pairs list.
     *
     * If the $atts list has unsupported attributes, then they will be ignored and
     * removed from the final returned list.
     *
     * @param array $pairs Entire list of supported attributes and their defaults.
     * @param array $atts User defined attributes in parsecode tag.
     * @param string $parsecode Optional. The name of the parsecode, provided for context to enable filtering
     * @return array Combined and filtered attribute list.
     * @throws \Qubus\Exception\Exception
     * @throws ReflectionException
     */
    public function atts(array $pairs, array $atts, string $parsecode = ''): array
    {
        $atts = (array) $atts;
        $out = [];
        foreach ($pairs as $name => $default) {
            if (array_key_exists($name, $atts)) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }
        }
        /**
         * Filters a parsecode's default attributes.
         *
         * If the third parameter of the $this->atts() method is present then this filter is available.
         * The third parameter, $parsecode, is the name of the parsecode.
         *
         * @param array  $out       The output array of parsecode attributes.
         * @param array  $pairs     The supported attributes and their defaults.
         * @param array  $atts      The user defined parsecode attributes.
         * @param string $parsecode The parsecode name.
         */
        if ($parsecode) {
            $out = Filter::getInstance()->applyFilter("atts_{$parsecode}", $out, $pairs, $atts, $parsecode);
        }

        return $out;
    }

    /**
     * Remove all parsecode tags from the given content.
     *
     * @param string $content Content to remove parsecode tags.
     * @return string Content without parsecode tags.
     */
    public function stripParsecodes(string $content): string
    {
        if (empty(self::$parsecodeTags) || !is_array(self::$parsecodeTags)) {
            return $content;
        }

        $pattern = $this->getRegex();

        return preg_replace_callback(
                "/$pattern/s",
                [
                        $this,
                        'stripParsecodeTag',
                ],
                $content
        );
    }

    public function stripParsecodeTag($m): string
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        return $m[1] . $m[6];
    }

    /**
     * @param string $pee
     * @param bool $br
     * @return array|string|string[]|null
     */
    public function autop(string $pee, bool $br = true): array|string|null
    {
        if (trim($pee) === '') {
            return '';
        }
        $pee = $pee . "\n"; // just to make things a little easier, pad the end
        $pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
        // Space things out a little
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
        $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        $pee = str_replace(["\r\n", "\r"], "\n", $pee); // cross-platform newlines
        if (strpos($pee, '<object') !== false) {
            $pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
            $pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
        }
        $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
        // make paragraphs, including one at the end
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';
        foreach ($pees as $tinkle) {
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
        }
        $pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
        $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
        if ($br) {
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', [$this, 'autopNewlinePreservationHelper'], $pee);
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
            $pee = str_replace('<TTPreserveNewline />', "\n", $pee);
        }
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        if (strpos($pee, '<pre') !== false) {
            $pee = preg_replace_callback('!(<pre[^>]*>)(.*?)</pre>!is', [$this, 'cleanPre'], $pee);
        }
        $pee = preg_replace("|\n</p>$|", '</p>', $pee);

        return $pee;
    }

    public function autopNewlinePreservationHelper($matches): array|string
    {
        return str_replace("\n", "<TTPreserveNewline />", $matches[0]);
    }

    public function unAutop($pee)
    {
        if (empty(self::$parsecodeTags) || !is_array(self::$parsecodeTags)) {
            return $pee;
        }

        $tagregexp = join('|', array_map('preg_quote', array_keys(self::$parsecodeTags)));

        $pattern = '/'
                . '<p>'                              // Opening paragraph
                . '\\s*+'                            // Optional leading whitespace
                . '('                                // 1: The parsecode
                . '\\['                          // Opening bracket
                . "($tagregexp)"                 // 2: parsecode name
                . '\\b'                          // Word boundary
                // Unroll the loop: Inside the opening parsecode tag
                . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
                . '(?:'
                . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
                . '[^\\]\\/]*'               // Not a closing bracket or forward slash
                . ')*?'
                . '(?:'
                . '\\/\\]'                   // Self closing tag and closing bracket
                . '|'
                . '\\]'                      // Closing bracket
                . '(?:'                      // Unroll the loop: Optionally, anything between the opening and closing parsecode tags
                . '[^\\[]*+'             // Not an opening bracket
                . '(?:'
                . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing parsecode tag
                . '[^\\[]*+'         // Not an opening bracket
                . ')*+'
                . '\\[\\/\\2\\]'         // Closing parsecode tag
                . ')?'
                . ')'
                . ')'
                . '\\s*+'                            // optional trailing whitespace
                . '<\\/p>'                           // closing paragraph
                . '/s';

        return preg_replace($pattern, '$1', $pee);
    }
}