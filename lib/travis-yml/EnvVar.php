<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

/**
 * Class EnvVar
 *
 * <https://github.com/travis-ci/travis-build/blob/master/lib/travis/build/env/var.rb>
 */
class EnvVar
{
    const PATTERN = '/
        (?:SECURE\ )? # optionally starts with "SECURE "
        ([\w]+)= # left hand side, var name
          ( # right hand side is one of
            (?:[^"\'`\\ ]?("|\'|`).*?((?<!\\\\)\\3))+ # things quoted by \',",`, optionally preceded, one or more times
            |
            (?:[^\\$]?\\$\\(.*?\\))+ # $(things), optionally preceded by non-$, one or more times
            |
            [^"\'\\ ]+ # some bare word, not containing " or \'
                      # (this includes many variations of things starting in $)
            |
            (?=\\s) # an empty string (look for a space ahead)
            |
            \\z # the end of the string
          )
        /x';

    /**
     * all environment variables in a sequence for one-by-one export
     *
     * @param $sequence
     * @return array array("FOO=BAR", "BAZ=QUX", ...)
     */
    public static function exportMapSequence($sequence)
    {
        $exports = array();
        if (!is_array($sequence)) {
            return $exports;
        }
        foreach ($sequence as $map) {
            if (!Node::isMap($map)) {
                continue;
            }
            foreach (self::exportMap($map) as $export) {
                $exports[] = $export;
            }
        }
        return $exports;
    }

    public static function exportMap(array $map)
    {
        $environ = array();
        foreach($map as $k => $v) {
            // quoting is inherited from yaml file ...
            $environ[] = sprintf('%s=%s', $k, $v);
        }
        return $environ;
    }

    /**
     * normalize any env_vars node in .travis.yml
     *
     * if a sequence is expected on the node, best is to make
     * the node a sequence already. works with string or
     * sequence transparently. secure: ... preserved.
     *
     * NOTE: SECURE aaa=zzz xxx=yyy turned into just a map.
     * secure properties deferred for now.
     *
     * @param string|array $node
     * @return array one or more env variable pairs as map, empty on parse error
     */
    public static function normalize($node)
    {
        if (is_scalar($node) || null === $node) { # typically string, but this is yaml
            return self::parse((string)$node);
        }
        if (Node::isMap($node)) {
            return $node;
        }
        if (is_array($node)) {
            $sequence = array();
            // re-implement inline to normalize a sequence
            foreach ($node as $sequenceItem) {
                if (is_scalar($sequenceItem) || null === $sequenceItem) { # typically string, but this is yaml
                    $result = self::parse($sequenceItem);
                    if (array() === $result) {
                        return array(); # hard exit if there is any parsing issue, standard behaviour
                    }
                    $sequence[] = $result;
                    continue;
                }
                if (array() === $sequenceItem  # keep any earlier parse failure/s
                ||  Node::isMap($sequenceItem) # already an environment map (or secure, which ever comes first)
                ) {
                    $sequence[] = $sequenceItem;
                    continue;
                }
                return array(); # not a string nor map - can not normalize sequence
            }
            return $sequence;
        }
        return array(); # not a string, map nor sequence - can not normalize
    }

    /**
     * parse string to env map
     *
     * @param string $line
     * @return array map of all environment variables (names as keys) from $line
     */
    public static function parse($line)
    {
        $map = array();
        foreach (self::scan($line) as $result) {
            $map[$result[0]] = $result[1];
        }
        return $map;
    }

    public static function scan($line)
    {
        $product = array();
        $offset = 0;
        while (true) {
            $result = preg_match(self::PATTERN, $line, $matches, PREG_OFFSET_CAPTURE, $offset);
            if (false === $result) throw new \UnexpectedValueException('preg_match(self::PATTERN) failed');
            if (0 === $result) break;
            $offset = $matches[0][1] + strlen($matches[0][0]);
            $product[] = array($matches[1][0], $matches[2][0]);
        }
        return $product;
    }
}
