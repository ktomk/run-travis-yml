<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

use PHPUnit\Framework\TestCase;

class TravisYmlTest extends TestCase
{
    /**
     * specifically the normalization of environment "FOO=BAR" to "FOO" => "BAR" maps
     */
    public function testDefineEnv()
    {
        $expected = TravisYml::envDefinition(null);
        $expected['global'] = array(array('FOO' => 'BAR'));
        $actual = TravisYml::envDefinition(array('global' => array('FOO' => 'BAR')));
        self::assertSame($expected, $actual, 'env normalization');
        $actual = TravisYml::envDefinition(array('global' => array('FOO=BAR')));
        self::assertSame($expected, $actual, 'env map notation from string');
    }

    public function testNormalizeBlank()
    {
        $expected = array();
        /** @noinspection AdditionOperationOnArraysInspection */
        $expected += TravisYml::$defaultYml;
        $expected['os'] = Node::normalizeSequence($expected['os']);
        $actual = TravisYml::normalizeYaml();
        self::assertSame($expected, $actual);
    }

    public function testNormalizeWithEnvGlobal()
    {
        $expected = array();
        $expected['env'] = array('global' => array(array('FOO' => 'BAR')), 'jobs' => array());
        $expected['env']['matrix'] = & $expected['env']['jobs'];
        /** @noinspection AdditionOperationOnArraysInspection */
        $expected += TravisYml::$defaultYml;
        $expected['os'] = Node::normalizeSequence($expected['os']);
        $actual = TravisYml::normalizeYaml(array('env' => array('global' => 'FOO=BAR')));
        self::assertSame($expected, $actual);
    }

    public function testNormalizeWithJobs()
    {
        $expected = array();
        $expected['jobs'] = TravisYml::$defaultJobs;
        $expected['jobs']['include'] = array(array('language' => 'php'));
        /** @noinspection AdditionOperationOnArraysInspection */
        $expected += TravisYml::$defaultYml;
        $expected['os'] = Node::normalizeSequence($expected['os']);
        $actual = TravisYml::normalizeYaml(array('jobs' => array('language' => 'php')));
        self::assertSame($expected, $actual);
    }

    public function testBareCustomStepScripts()
    {
        $file = __DIR__ . '/../../test/file/php-cs-fixer.travis.yml';
        $config = TravisYml::openFile($file);
        $scripts = $config->bareCustomStepScripts();
        self::assertIsArray($scripts);
        self::assertSame(array('before_install'), array_keys($scripts), 'correct file');
    }

    public function testEnv()
    {
        $file = __DIR__ . '/../../test/file/php-cs-fixer.travis.yml';
        $config = TravisYml::openFile($file);
        $actual = $config->env();
        $expected = array(
            'global' => array(
                array('DEFAULT_COMPOSER_FLAGS' => '"--optimize-autoloader --no-interaction --no-progress"'),
                array('COMPOSER_FLAGS' => '""'),
            ),
            'jobs' => array(),
        );
        $expected['matrix'] = &$expected['jobs'];
        self::assertSame($expected, $actual, 'env');

        $actual['matrix'][] = 'test';
        $expected['jobs'][] = 'test';
        self::assertSame($expected, $actual, 'aliasing');
    }

    public function testLanguage()
    {
        $file = __DIR__ . '/../../test/file/build-stages.travis.yml';
        $config = TravisYml::openFile($file);
        self::assertSame('ruby', $config->language()->name());
    }

    public function testBuildStages()
    {
        $file = __DIR__ . '/../../test/file/build-stages.travis.yml';
        $config = TravisYml::openFile($file);

        $actual = $config->jobStepScripts();
        $expected = array(
            'include.1' => array(
                'stage' => 'test',
                'script' => './test 1',
            ),
            'include.2' => array(
                'script' => './test 2',
            ),
            'include.3' => array(
                'stage' => 'deploy',
                'script' => './deploy',
            ),
        );
        self::assertSame($expected, $actual);
    }

    public function testMultiStage()
    {
        $file = __DIR__ . '/../../test/file/php-cs-fixer.travis.yml';
        $config = TravisYml::openFile($file);
        $expected = array_flip(array_map(function($v) {
            return sprintf('include.%d', $v);
        }, range(1, 9)));
        $actual = $config->jobStepScripts();
        self::assertIsArray($actual);
        $excerpt = array_intersect_key($actual, $expected);
        self::assertCount(count($expected), $excerpt);
    }
}
