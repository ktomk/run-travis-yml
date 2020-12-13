<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

use Ktomk\Pipelines\Yaml\Yaml;

/**
 * the .travis.yml config
 *
 * <https://config.travis-ci.com/>
 */
class TravisYml
{
    /**
     * default .travis.yml document
     *
     * @var string[]
     */
    public static $defaultYml = array(
        'language' => 'ruby',
        'os' => 'linux',
        'dist' => 'xenial',
    );

    public static $defaultJobs = array(
        'include' => array(),
        'exclude' => array(),
        'allow_failures' => array(),
        'fast_finish' => array(),
    );

    /**
     * Job Lifecycle
     *
     * <https://docs.travis-ci.com/user/job-lifecycle/>
     */
    public static $lifecyle = array(
        'apt addons'        => array('OPTIONAL Install'),
        'cache components'  => array('OPTIONAL Install'),
        'before_install'    => array('CUSTOM'),
        'install'           => array('CUSTOM'),
            # install: ./install-dependencies.sh - single script
            # install: skip                      - skip the installation phase
        'before_script'     => array('CUSTOM'),
        'script'            => array('CUSTOM'),
            # script a.k.a. the build phase
        'before_cache'      => array('OPTIONAL Caching is effective'),
        'after_success'     => array('TRAVIS_TEST_RESULT = 0'),
        'after_failure'     => array('TRAVIS_TEST_RESULT = 1'),
        'before_deploy'     => array('OPTIONAL Deployment is active'),
        'deploy'            => array('OPTIONAL Deployment is active'),
        'after_deploy'      => array('OPTIONAL Deployment is active'),
            # the deploy steps are skipped if the build is broken
            # before/after are with multiple providers
            # deploy needs a builder of it's own
        'after_script'      => array('CUSTOM'),
    );

    /**
     * Default Supported ordered Lifecycle Steps
     *
     * @var string[]
     */
    public static $customSteps = array('before_install', 'install', 'before_script', 'script', 'after_script');

    /**
     * Os-es
     */
    public static $oses = array('linux', 'osx', 'windows');

    /**
     * Arch-es
     */
    public static $arches = array('amd64', 'arm64', 'ppc64le', 's390x');

    /**
     * Languages
     *
     * @var array
     */
    public static $languages = array(
        'android',
        'c' => array(
            'default' => array('install' => null, 'script' => '	./configure && make && make test'),
            'matrix' => array('env', 'compiler'),
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
            'matrix' => array('env', 'php'),
        ),
        'python',
        'r',
        'ruby' => array(
            'default' => array('install' => 'bundle install --jobs=3 --retry=3', 'script' => 'rake'),
            'matrix' => array('env', 'rvm', 'gemfile', 'jdk'),
        ),
        'rust',
        'scala',
        'shell',
        'smalltalk'
    );

    /**
     * @var array
     */
    private $yml;

    public static function loadArray(array $yml = array())
    {
        $yml = self::normalizeYaml($yml);

        return new TravisYml($yml);
    }

    /**
     * normalize the .travis.yml document
     *
     * @param array $yml
     * @return array
     */
    public static function normalizeYaml(array $yml = array())
    {
        // step 1: defaults
        /** @noinspection AdditionOperationOnArraysInspection */
        $yml += self::$defaultYml;
        // step 2: normalize root nodes
        $yml['language'] = Node::normalizeString($yml['language']);
        $yml['os'] = Node::normalizeSequence($yml['os']);
        $yml['dist'] = Node::normalizeString($yml['dist']);
        isset($yml['env']) && $yml['env'] = self::envDefinition($yml['env']);
        isset($yml['jobs']) && $yml['jobs'] = self::jobsDefinition($yml['jobs']);
        return $yml;
    }

    /**
     * @param $path
     * @return array
     */
    public static function loadFile($path)
    {
        $yml = Yaml::file($path);
        if (!is_array($yml)) {
            throw new \InvalidArgumentException(sprintf("failed to parse file: '%s'", $path));
        }
        return $yml;
    }

    public static function openFile($path)
    {
        return self::loadArray(self::loadFile($path));
    }

    /**
     * filter an array with step names to only those in custom-steps
     *
     * @param array|string[] $steps
     * @return array|string[]
     */
    public static function filterSteps(array $steps)
    {
        return Node::filterSequence($steps, self::$customSteps);
    }

    public function __construct(array $yml)
    {
        $this->yml = $yml;
    }

    /**
     * env struct incl. matrix alias
     *
     * <https://config.travis-ci.com/ref/env>
     *
     * @return array|null
     */
    public function env()
    {
        return self::envDefinition(Node::item($this->yml, array('env')));
    }

    /**
     * env struct incl. matrix alias
     *
     * <https://config.travis-ci.com/ref/env>
     *
     * @param array|string $node value to create the environment node from
     * @return array
     */
    public static function envDefinition($node)
    {
        $defaultPrefix = 'jobs';  /* a.k.a. 'matrix' */
        $env = array(
            'global' => array(),
            $defaultPrefix => array(),
        );
        $alias = array('matrix' => $defaultPrefix);
        $env = Node::aliasMap($env, $alias);
        if (isset($node)) {
            $merge = Node::filterAliasMap($node, $alias);
            $merge = Node::normalizeMapEx($merge, $defaultPrefix, array_keys($env));
            $env = Node::mergeMapWithAlias($env, $merge, $alias);
            $env['global'] = Node::normalizeSequence($env['global']);
            $env['global'] = array_map(array(__CLASS__, 'envVariableNormalize'), $env['global']);
            $env[$defaultPrefix] = array_map(array(__CLASS__, 'envVariableNormalize'), $env[$defaultPrefix]);
        }
        return $env;
    }

    /**
     * normalize environment variables for export
     *
     * @param array $envs
     * @return array
     */
    public static function envVariables(array $envs)
    {
        $environ = array();
        foreach ($envs as $env) {
            if (is_string($env)) {
                $environ[] = $env;
                continue;
            }
            foreach($env as $k => $v) {
                // quoting is inherited from yaml file ...
                $environ[] = sprintf('%s=%s', $k, $v);
            }
        }
        return $environ;
    }

    private static function envVariableNormalize($var)
    {
        if (is_string($var)) {
            list($name, $value) = explode('=', $var, 2) + array(1 => null);
            $var = array($name => $value);
        }
        return $var;
    }

    public static function jobsDefinition($node)
    {
        $defaultPrefix = 'include';
        $jobs = self::$defaultJobs;
        $alias = array('allowed_failures' => 'allow_failures', 'fast_failure' => 'fast_finish');
        if (isset($node)) {
            $merge = Node::filterAliasMap($node, $alias);
            $merge = Node::normalizeMapEx($merge, $defaultPrefix, array_keys($jobs));
            $jobs = Node::mergeMapWithAlias($jobs, $merge, $alias);
            $jobs['exclude'] = Node::normalizeSequence($jobs['exclude']);
            $jobs['allow_failures'] = Node::normalizeSequence($jobs['allow_failures']);
            $jobs['fast_finish'] = Node::normalizeSequence($jobs['fast_finish']);
            $jobs = Node::mergeMapWithAlias($jobs, $merge);
        }
        return $jobs;
    }

    /**
     * jobs struct
     *
     * @return array|array[]
     */
    public function jobs()
    {
        return self::jobsDefinition(Node::item($this->yml, array('jobs')));
    }

    /**
     * language from .travis.yml
     */
    public function language()
    {
        // just cautious: does not happen any longer since all incoming yml
        // is normalized and language has a default.
        if (!isset($this->yml['language'])) {
            return new Language($this, 'unknown');
        }

        $lang = $this->yml['language'];
        $defined = array_key_exists($lang, self::$languages);
        $known = $defined || in_array($lang, self::$languages, true);

        return new Language($this, $lang);
    }

    /**
     * @return array|string[] language names
     */
    public static function languages()
    {
        $list = array();
        foreach(self::$languages as $k => $v) {
            $list[] = is_string($v) ? $v : $k;
        }
        return $list;
    }

    /**
     * all job step scripts from file
     */
    public function jobStepScripts()
    {
        $list = array();

        # at least one job is in file (the non-matrix matrix)
        $bare = $this->bareCustomStepScripts();

        # is there a matrix (normally langauge)
        $language = $this->language();
        $language->matrixKeys();
        if (isset($this->yml[$language->name()])) {
            $list['language'] = $bare;
        }

        # jobs.include
        $jobs = $this->jobs();
        foreach($jobs['include'] as $index => $include) {
            $job = Node::mergeMapWithAlias($bare, $include);
            $list['include.'.($index+1)] = $job;
        }

        return $list;
    }

    /**
     * custom lifecycle steps bare from config, basically the originally
     * implementation. here explicitly filtered so that no non-custom
     * steps can be run.
     *
     * @return array
     */
    public function bareCustomStepScripts()
    {
        return array_intersect_key($this->yml, array_flip(self::$customSteps));
    }
}
