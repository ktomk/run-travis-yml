<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{
    public function testNormalizeSequence()
    {
        $input = array(
            'language' => 'ruby',
            'os'  =>  'linux',
            'dist' => 'trusty',
        );
        self::assertSame(array($input), Node::normalizeSequence($input));
    }

    public function provideNormalizeMapMaps()
    {
        return array(
            array(null, null, array()),
            array(array(), null, array()),
            array(array(), 'foo', array('foo' => array())),
            array(null, 'foo', array('foo' => array())),
            array(array('foo' => array()), 'foo', array('foo' => array())),
            array(array('foo' => 'bar'), 'foo', array('foo' => 'bar')),
            array(array('foo' => 'bar'), 'baz', array('foo' => 'bar')),
        );
    }

    /**
     * the normalizeMap function does standard normalization of a map if
     * the node value itself is not a map (this is normally announced so),
     * otherwise see below at-see annotation.
     *
     * @param $input
     * @param $defaultPrefix
     * @param array $expected
     *
     * @dataProvider provideNormalizeMapMaps
     * @see Node::normalizeMapEx() for more operands.
     */
    public function testNormalizeMap($input, $defaultPrefix, array $expected)
    {
        $actual = Node::normalizeMap($input, $defaultPrefix);
        self::assertSame($expected, $actual);
    }

    public function provideNormalizeMapExMaps()
    {
        return array(
            array('FOO=BAR', 'jobs', array('jobs' => array('FOO=BAR'))),
            array(array('FOO=BAR'), 'jobs', array('jobs' => array('FOO=BAR'))),
            array(null, 'jobs', array('jobs' => array())),
            array(null, null, null),
            array(array('jobs' => 'FOO=BAR'), 'jobs', array('jobs' => array('FOO=BAR'))),
            array(array('jobs' => array('FOO=BAR')), 'jobs', array('jobs' => array('FOO=BAR'))),
            array(array('globals' => 'FOO=BAR'), null, array('globals' => 'FOO=BAR')),

            'map without default prefix key and prototype'
            =>
            array(array('globals' => 'FOO=BAR'), 'jobs', array('globals' => 'FOO=BAR', 'jobs' => array()), array('globals')),

            'map turns into sequence of one map'
            => array(
                array('language' => 'php'),
                'include',
                array('include' => array(array('language' => 'php'))),
            ),

            'map on default turns into sequence with one map'
            => array(
                array('include' => array('language' => 'php')),
                'include',
                array('include' => array(array('language' => 'php'))),
            ),

            'sequence of one map on default stays sequence with one map'
            => array(
                array('include' => array(array('language' => 'php'))),
                'include',
                array('include' => array(array('language' => 'php'))),
            ),

            'map on default that matches type stays'
            => array(
                array('exclude' => array('language' => 'php')),
                'include',
                array('exclude' => array('language' => 'php'), 'include' => array()),
                array('exclude'),
            ),
        );
    }

    /**
     * @dataProvider provideNormalizeMapExMaps
     * @param mixed $map
     * @param null|string $defaultPrefixKey
     * @param array $expected
     * @param array $defaultKeys
     */
    public function testNormalizeMapEx($map, $defaultPrefixKey, $expected, array $defaultKeys = array())
    {
        $actual = Node::normalizeMapEx($map, $defaultPrefixKey, $defaultKeys);
        self::assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testFilterAliasMap()
    {
        $alias = array('matrix' => 'jobs');
        self::assertNull(Node::filterAliasMap(null, $alias));

        $expected = range(4, 14);
        self::assertSame($expected, Node::filterAliasMap($expected, $alias));

        $expected = array('jobs' => 'bar');
        $actual = Node::filterAliasMap(array('matrix' => 'bar'), $alias);
        self::assertSame($expected, $actual);
    }
}
