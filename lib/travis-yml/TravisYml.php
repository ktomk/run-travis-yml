<?php
/** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

use IntlChar;
use InvalidArgumentException;
use Ktomk\Pipelines\Yaml\Yaml;
use Patchwork\Utf8\Bootup;

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
        'fast_finish' => false,
    );

    public static $jobDimensions = array('include', 'exclude', 'allow_failures');

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
     * @see $allSteps
     */
    public static $customSteps = array('before_install', 'install', 'before_script', 'script', 'after_script');

    /**
     * All Supported ordered Lifecycle Steps
     *
     * These are the known and allowed ones for step input. Some of them are actually internal to travis-build
     * and are not supported by the standard run. Maybe it helps to have them all in so that users can trigger
     * them explicitly in case it fits their needs (e.g. after_success as they can always assume success until
     * after_success / after_failure are actually supported) or trigger any of the deploy scripts by sheer will.
     *
     * <https://github.com/travis-ci/travis-build/blob/master/lib/travis/build/stages.rb>
     *
     * @var string[]
     * @see $customSteps
     */
    public static $allSteps = array('before_install', 'install', 'before_script', 'script', 'before_cache', 'after_success', 'after_failure', 'after_script', 'before_deploy', 'after_deploy');

    /**
     * Os-es
     */
    public static $oses = array('linux', 'osx', 'windows');

    /**
     * Arch-es
     */
    public static $arches = array('amd64', 'arm64', 'ppc64le', 's390x');

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
        // step 0: root aliases
        // the key 'matrix' is an alias for 'jobs', using 'jobs'
        $yml = Node::filterAliasMap($yml, array('matrix' => 'jobs'));

        // step 1: defaults (incl. putting them on top)
        $yml = array_replace(self::$defaultYml, $yml);

        // step 2: normalize root nodes
        $yml['language'] = Node::normalizeString($yml['language']);
        $yml['os'] = Node::normalizeSequence($yml['os']);
        $yml['dist'] = Node::normalizeString($yml['dist']);
        isset($yml['env']) && $yml['env'] = self::envDefinition($yml['env']);
        isset($yml['jobs']) && $yml['jobs'] = self::jobsDefinition($yml['jobs']);
        $yml = Node::copyNormalizeSequence($yml, self::$allSteps, $yml);
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
            throw new InvalidArgumentException(sprintf("failed to parse file: '%s'", $path));
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
        return Node::filterSequence($steps, self::$allSteps);
    }

    public static function fmtBuildStageName($stage)
    {
        self::initOnce();
        return preg_replace_callback('(\\b(\\p{L&}))u', function($matches) {
            return mb_strtoupper($matches[0], 'utf-8');
        }, $stage, 1);
    }

    public static function fmtBuildJobName($name)
    {
        self::initOnce();
        return preg_replace_callback('(\\b(\\p{L&}))u', function($matches) {
            return mb_strtoupper($matches[0], 'utf-8');
        }, $name, 1);
    }

    public static function initOnce()
    {
        static $once = 0;
        $once++ || Bootup::initAll();
    }

    public function __construct(array $yml)
    {
        self::initOnce();
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
        if (isset($node)) {
            $merge = Node::filterAliasMap($node, $alias);
            $merge = Node::normalizeMapEx($merge, $defaultPrefix, array_keys($env));
            $env = Node::mergeMapWithAlias($env, $merge, $alias);
            $env['global'] = EnvVar::normalize(Node::normalizeSequence($env['global']));
            $env[$defaultPrefix] = EnvVar::normalize(Node::normalizeSequence($env[$defaultPrefix]));
        }
        return $env;
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
            foreach (self::$jobDimensions as $prop) {
                $jobs[$prop] = self::normalizeJobs($jobs[$prop]);
            }
        }
        return $jobs;
    }

    /**
     * @param array $node one or more job matrix entries
     * @return array
     */
    public static function normalizeJobs($node)
    {
        $sequence = Node::normalizeSequence($node);
        $stage = 'test';
        if (!node::arrayIsSequence($sequence)) {
            return $sequence;
        }
        foreach ($sequence as $i => $job) {
            if (!Node::isMap($job)) {
                continue; # not/broken job matrix entry
            }
            // env vars
            $job += array('env' => array());
            $job['env'] = EnvVar::normalize(Node::normalizeSequence($job['env']));
            // stage name
            $job['stage'] = $stage = trim(Node::item($job, 'stage', $stage)) ?: $stage;
            // job name - optional, but if set should be string
            if (isset($job['name'])) {
                $job['name'] = trim($job['name']) ?: null;
            }
            $sequence[$i] = $job;
        }
        return $sequence;
    }

    /**
     * language from .travis.yml
     *
     * @deprecated use Language class instead
     */
    public function language()
    {
        return new Language($this->yml['language']);
    }

    public static function defineRootJob(array $yml)
    {
        $root = array();
        $root['language'] = $yml['language'];
        # os -> string, deferred as currently no matrix based on os-es
        # dist -> string, deferred as currently no matrix based on os-es
        # arch -> same same
        # osx_image -> same same
        # sudo -> ?hmm same same?
        $root['env'] = Node::item($yml, array('env', 'global'), array());
        # compiler -> no support yet
        $root['name'] = null; # the root job has no name
        $root['stage'] = null; # the root job is not associated to any stage
        $root = Node::copy($root, self::$allSteps, $yml);
        return $root;
    }

    /**
     * define matrix job based on $job inheriting from $root job
     *
     * @param array $root
     * @param array $job
     * @param bool $globalEnv
     * @param bool $rootSteps
     * @return array
     */
    public static function defineBuildJob(array $root, array $job, $globalEnv = false, $rootSteps = false)
    {
        $matrix = Node::copy(array('language' => null), array('language', 'env', 'name', 'stage'), $job);
        $matrix['env'] = array();
        $matrix = array_replace($matrix, $root);
        $globalEnv || $matrix['env'] = array();
        $rootSteps || $matrix = Node::remove($matrix, self::$allSteps);
        $matrix = Node::copy($matrix, 'language', $job);
        if (!isset($matrix['language'])) { unset($matrix['language']); }
        $matrix = Node::append($matrix, 'env', $job);
        $matrix = Node::copy($matrix, array('name', 'stage'), $job);
        $matrix = Node::copyNormalizeSequence($matrix, self::$allSteps, $job);
        // clean name if NULL (root job serves a place-holder)
        if (!isset($matrix['name'])) {
            unset($matrix['name']);
        }
        return $matrix;
    }

    /**
     * @param string $key of run-job, empty string for the default run-job, if not a key, searches for name
     * @return array job
     */
    public function runJob($key)
    {
        if ('' === $key) {
            return $this->defaultRunJob();
        }

        $jobs = $this->jobStepScripts();
        if (!isset($jobs[$key])) {
            foreach ($jobs as $k => $v) {
                if (!isset($v['name'])) {
                    continue;
                }
                if (mb_strtolower($v['name'], 'utf-8') !== mb_strtolower($key)) {
                    continue;
                }
                $key = $k;
                break;
            }
        }

        if (!isset($jobs[$key])) {
            throw new InvalidArgumentException(sprintf('no such run-job: "%s"', $key));
        }

        $job = $jobs[$key];
        if (!in_array($key, array('root', 'bare'), true)) {
            $root = $this->defaultRunJob();
            $job = self::defineBuildJob($root, $job, true, true);
        }

        return $job;
    }

    /**
     * the original default run job
     *
     * that are the step scripts from root plus global env
     * if available.
     *
     * @return array
     */
    public function defaultRunJob()
    {
        $jobs = $this->jobStepScripts();
        if (isset($jobs['root'])) {
            $job = $jobs['root'];
        } else if (isset($jobs['bare'])) {
            $job = $jobs['bare'];
        } else {
            $job  = array();
        }

        return $job;
    }

    /**
     * all jobs from file of which step scripts could be executed (keyed)
     *
     * now with environment variables (global.env + job.env)
     *
     * @psalm-return array<string, array<string, string|array>>
     * @return array[]
     */
    public function jobStepScripts()
    {
        $list = array();
        $yml = $this->yml;

        # all steps bare from the root (not a real job), makes only sense if it contains any script
        # this was the default until global env vars were supported (- v1.5.0)
        $bare = Node::copy(array(), self::$allSteps, $yml);
        $bareHasSteps = !empty($bare);
        if ($bareHasSteps) {
            $list['bare'] = $bare;
        }

        # the root job (has default stage, step scripts from root, global.env)
        # similar to the previous plan['default'] which ain't any longer, also the previous run
        # this is the default since global env vars were supported (v1.5.0 - )
        $root = self::defineRootJob($yml);
        $globalEnv = !empty($root['env']);
        if ($bareHasSteps && $globalEnv) {
            $list['root'] = $root;
        }

        # matrix env vars (if set and there are at least one matrix/job env vars entry)
        if ($bareHasSteps && isset($yml['env']['jobs'])) {
            foreach ($yml['env']['jobs'] as $index => $envVars) {
                $env = self::defineBuildJob($root, array('env' => array($envVars)));
                $list['env.' . ($index + 1)] = $env;
                unset($env);
            }
        }

        # jobs' dimensions (include + exclude, allow_failures dropped)
        foreach (array_diff(self::$jobDimensions, array('allow_failures')) as $dimension) {
            if (!isset($yml['jobs'][$dimension])) {
                continue;
            }
            $stage = 'test';
            foreach ($yml['jobs'][$dimension] as $index => $jobMatrixEntry) {
                $job = self::defineBuildJob($root, $jobMatrixEntry);
                $stage = $job['stage'] = trim(Node::item($job, 'stage', $stage)) ?: $stage;
                $list[$dimension . '.' . ($index + 1)] = $job;
            }
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

    /**
     * @return array
     */
    public function getDocument()
    {
        return $this->yml;
    }
}
