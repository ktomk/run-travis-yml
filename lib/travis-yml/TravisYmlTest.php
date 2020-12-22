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

    public function testDefineRootJob()
    {
        $yml = TravisYml::normalizeYaml();
        $yml['env'] = TravisYml::envDefinition(array('global' => 'FOO=BAR', 'jobs' => 'BAZ=QUX'));
        $yml['install'] = 'bundle install --jobs=3 --retry=3';
        $yml['script'] = 'rake';
        $expected = array(
            'language' => 'ruby',
            'env' => array(array('FOO' => 'BAR')),
            'name' => null,
            'stage' => null,
            'install' => $yml['install'],
            'script' => $yml['script'],
        );
        self::assertSame($expected, TravisYml::defineRootJob($yml));

        return TravisYml::defineRootJob($yml);
    }

    /**
     * @param array $root
     * @depends testDefineRootJob
     */
    public function testDefineMatrixJob(array $root)
    {
        $job = array();
        $job['install'] = 'bundle install';
        $job['env'] = array(array('STEP' => 'COOLDOWN'));
        $job['name'] = 'test cooldown';
        $expected = array(
            'language' => 'ruby',
            'env' => array(array('STEP' => 'COOLDOWN')),
            'name' => $job['name'],
            'stage' => null,
            'install' => array($job['install']),
        );
        self::assertSame($expected, TravisYml::defineBuildJob($root, $job), 'without global env and root steps');

        $expected['env'] = array(array('FOO' => 'BAR'), array('STEP' => 'COOLDOWN'));
        self::assertSame($expected, TravisYml::defineBuildJob($root, $job, true), 'with global env');

        $expected['script'] = $root['script'];
        self::assertSame($expected, TravisYml::defineBuildJob($root, $job, true, true), 'with global env and root steps');

        $expected['env'] = $job['env'];
        self::assertSame($expected, TravisYml::defineBuildJob($root, $job, false, true), 'without global env and with root steps');
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
        /** @noinspection AdditionOperationOnArraysInspection */
        $expected += TravisYml::$defaultYml;
        $expected['env'] = array('global' => array(array('FOO' => 'BAR')), 'jobs' => array());
        $expected['os'] = Node::normalizeSequence($expected['os']);
        $actual = TravisYml::normalizeYaml(array('env' => array('global' => 'FOO=BAR')));
        self::assertSame($expected, $actual);
    }

    public function testNormalizeWithJobs()
    {
        $expected = array();
        /** @noinspection AdditionOperationOnArraysInspection */
        $expected += TravisYml::$defaultYml;
        $expected['jobs'] = TravisYml::$defaultJobs;
        $expected['jobs']['include'] = array(array('language' => 'php', 'env' => array(), 'stage' => 'test'));
        $expected['os'] = Node::normalizeSequence($expected['os']);
        $actual = TravisYml::normalizeYaml(array('jobs' => array('language' => 'php')));
        self::assertSame($expected, $actual);

        // matrix alias
        $actual = TravisYml::normalizeYaml(array('matrix' => array('language' => 'php')));
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
        self::assertSame($expected, $actual, 'env');
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
                'language' => 'ruby',
                'env' => array(),
                'stage' => 'test',
                'script' => array('./test 1'),
            ),
            'include.2' => array(
                'language' => 'ruby',
                'env' => array(),
                'stage' => 'test',
                'script' => array('./test 2'),
            ),
            'include.3' => array(
                'language' => 'ruby',
                'env' => array(array('FOO' => 'BAZ')),
                'stage' => 'deploy',
                'script' => array('./deploy'),
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

    public function testFmtBuildJobName()
    {
        $expected = '7.3 | With migration rules';
        $actual = TravisYml::fmtBuildJobName('7.3 | with migration rules');
        self::assertSame($expected, $actual);
    }
}
