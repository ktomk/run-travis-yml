<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

/**
 * cli args handling class
 *
 * hands-on port from scripts with a fluent interface to share
 * between multiple scripts.
 *
 * incl. deferred testing for options, their arguments - not
 * yet for operands.
 */
class Args
{
    /**
     * @var array argv to operate on
     */
    private $argv;

    /**
     * @var callable
     */
    private $getenvImpl;

    /**
     * @var array deferred options tests
     */
    private $options;

    /**
     * load .travis.yml file
     *
     * @param $path
     * @return TravisYml config
     */
    public static function loadConfig($file)
    {
        try {
            $config = TravisYml::openFile($file);
        } catch (\Exception $e) {
            fprintf(STDERR, "fatal: %s\n", $e->getMessage());
            exit(1);
        }
        return $config;
    }

    public static function runJob(TravisYml $config, $key)
    {
        $key = trim($key);
        try {
            $job = $config->runJob($key);
        } catch (\Exception $e) {
            fprintf(STDERR, "fatal: %s\n", $e->getMessage());
            exit(1);
        }
        return $job;
    }

    /**
     * array as environment functor creation
     *
     * @param array $environ key => val map (not list of KEY=VAL lines)
     * @return \Closure
     */
    public static function environ(array $environ)
    {
        return function($varname) use ($environ) {
            return isset($environ[$varname]) ? $environ[$varname] : false;
        };
    }

    /**
     * @param array $argv
     * @param null $env
     * @return Args
     */
    public static function create(array $argv, $env = null)
    {
        return new self($argv, $env);
    }

    /**
     * Args constructor.
     * @param array $argv
     * @param string $getenvImpl
     */
    public function __construct(array $argv, $getenvImpl = null) {
        $this->options = array();
        $this->argv = $argv;
        if (null === $getenvImpl) {
            $getenvImpl = 'getenv';
        }

        $this->getenvImpl = $getenvImpl;
    }

    /**
     * @param string $var , name of, in environment
     * @param $argument
     * @param null $default
     * @return $this
     */
    public function env($var, &$argument, $default = null)
    {
        $getenvImpl = $this->getenvImpl;
        $test = $getenvImpl($var);
        if (false !== $test) {
            $argument = $test;
        } else {
            // set default only if explicitly passed
            if (3 === count(func_get_args())) {
                $argument = $default;
            }
        }

        return $this;
    }

    /**
     * @param string|string[] $args
     * @param $argument
     * @param null $default
     * @return $this
     */
    public function optArg($args, &$argument, $default = null)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        if (!$this->testOptArg($args, $argument)) {
            // set default only if explicitly passed
            if (3 === count(func_get_args())) {
                $argument = $default;
            }
            $this->options[] = array(array($this, 'testOptArg'), array($args, &$argument));
        }
        $this->reTest($this->options);
        return $this;
    }

    /**
     * @param string|string[] $args
     * @param $argument
     * @return $this
     */
    public function optFlag($args, &$argument)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $argument = false;
        if (!$this->testOptFlag($args, $argument)) {
            $this->options[] = array(array($this, 'testOptFlag'), array($args, &$argument));
        }
        return $this;
    }

    /**
     * parse operands as stages
     *
     * NOTE: experimental, no `--` handling and also filtering is applied later but
     *       could be here. misses a generic test operands first.
     *
     * @param $argument
     * @param $default
     */
    public function opStage(&$argument, $default)
    {
        $argv = &$this->argv;
        if (isset($argv[1]) && '' !== trim($argv[1])) {
            $argument = array_reduce(array_splice($argv, 1), function ($carry, $item) {
                return array_merge($carry, array_filter(preg_split('~[^a-z_]+~i', $item)));
            }, array());
        } else {
            $argument = $default;
        }
    }

    /**
     * argv terminator
     *
     * @return array
     */
    public function getArgv()
    {
        return $this->argv;
    }

    /* re-testing implementations (deferred options and arguments parsing) */

    private function reTest(&$options)
    {
        foreach ($options as $key => $test) {
            $result = call_user_func_array($test[0], $test[1]);
            if ($result) {
                unset($options[$key]);
            }
        }
    }

    private function testOptArg(array $args, &$argument)
    {
        $argv = &$this->argv;
        if (isset($argv[1]) && in_array($argv[1], $args, true)) {
            if (!isset($argv[2])) {
                fprintf(STDERR, "fatal: %s needs an argument\n", $argv[1]);
                exit(1);
            }
            $argument = $argv[2];
            array_splice($argv, 1, 2);
            return true;
        }
        return false;
    }

    private function testOptFlag(array $args, &$argument)
    {
        $argv = &$this->argv;
        if (isset($argv[1])&& in_array($argv[1], $args, true)) {
            $argument = true;
            array_splice($argv, 1, 1);
            return true;
        }
        return false;
    }
}
