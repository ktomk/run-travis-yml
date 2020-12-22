<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

/**
 * experimental
 */
class Language
{
    /**
     * Languages
     *
     * @var array
     */
    public static $languages = array(
        'android',
        'c' => array(
            'default' => array('install' => null, 'script' => '	./configure && make && make test'),
            'matrix' => array('compiler'),
        ),
        'clojure',
        'cpp',
        'crystal',
        'csharp',
        'd',
        'dart',
        'elixir',
        'elm',
        'erlang',
        'generic',
        'go',
        'groovy',
        'hack',
        'haskell',
        'haxe',
        'java',
        'julia',
        'nix',
        'node_js',
        'objective-c',
        'perl',
        'perl6',
        'php' => array(
            'default' => array('install' => null, 'script' => 'phpunit'),
            'matrix' => array('php'),
        ),
        'python',
        'r',
        'ruby' => array(
            'default' => array('install' => 'bundle install --jobs=3 --retry=3', 'script' => 'rake'),
            'matrix' => array('rvm', 'gemfile', 'jdk'),
        ),
        'rust',
        'scala',
        'shell',
        'smalltalk'
    );

    /**
     * @var string
     */
    private $name;

    /**
     * each language has expansion keys for the matrix next to the
     * expansion keys within the root (e.g. env[.jobs] or os)
     *
     * @param string $name
     * @return array|string[] expansion keys by $name langauge
     */
    public static function expansionKeys($name)
    {
        $name = self::normalizeName($name);
        $keys = Node::item(self::$languages, array($name, 'matrix'), array());
        if (array() === $keys && in_array($name, self::$languages, true)) {
            $keys = array($name);
        }
        return $keys;
    }

    /**
     * travis has some language name normalization as with aliases
     * and regular expressions, this method is the shelf for that.
     *
     * @param string $name
     * @return string
     */
    public static function normalizeName($name)
    {
        return trim($name);
    }

    /**
     * Language constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = self::normalizeName($name);
    }

    public function name()
    {
        return $this->name;
    }

    /**
     * matrix keys can be additional ones specified by language
     *
     * this one always adds env despite it is not language specific. this
     * is a lack of the interface, language related things may need to
     * move TravisYml into here and then more static methods.
     *
     * Language support currently is weak and deferred.
     *
     * WORDING NOTE: "expansion keys" <https://docs.travis-ci.com/user/build-matrix/>
     * expansion keys are taken their first value on jobs.include.job entry not
     * having the expansion key set. -> this is necessary for a true build matrix
     *
     * FIXME(tk): define all languages
     *
     * @return array
     * @deprecated
     * @see expansionKeys
     */
    public function matrixKeys()
    {
        $keys = self::expansionKeys($this->name);
        $keys[] = 'env';
        return $keys;
    }
}
