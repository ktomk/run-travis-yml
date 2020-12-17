<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

use PHPUnit\Framework\TestCase;

class EnvVarTest extends TestCase
{
    public function testNormalize()
    {
        self::assertSame(array(), EnvVar::normalize(''));
        self::assertSame(array(), EnvVar::normalize(array()));
        self::assertSame(array('A'=>'B'), EnvVar::normalize('A=B'));
        self::assertSame(array('A'=>'B', 'C'=>'D'), EnvVar::normalize('A=B C=D'));
        self::assertSame(
            array(array('A' => 'B', 'X' => 'Y'), array('C' => 'D', 'E' => 'F')),
            EnvVar::normalize(array('A=B X=Y', array('C' => 'D', 'E' => 'F')))
        );
    }

    public function testParse()
    {
        $line = 'VERSION="main-9.4.5" DB=mysql SAMPLE_DATA=no';
        $expected = array('VERSION' => '"main-9.4.5"', 'DB' => 'mysql', 'SAMPLE_DATA' => 'no');
        $actual = EnvVar::parse($line);
        self::assertSame($expected, $actual);
    }

    /**
     * port from spec.rb, regex dialect is close to phps' libpcre thought. these specs
     * confirm the port
     */
    public function provideScanSamples()
    {
        return array(
            'parses SECURE FOO=foo BAR=bar'
            => array('SECURE FOO=foo BAR=bar', array(array("FOO", "foo"), array("BAR", "bar"))),

            'parses FOO=foo BAR=bar'
            => array('FOO=foo BAR=bar', array(array('FOO', 'foo'), array('BAR', 'bar'))),

            'parses FOO="" BAR=bar'
            => array('FOO="" BAR=bar', array(array('FOO', '""'), array('BAR', 'bar'))),

            'parses FOO="foo" BAR=bar'
            => array('FOO="foo" BAR=bar', array(array('FOO', '"foo"'), array('BAR', 'bar'))),

            'parses FOO="foo" BAR="bar"'
            => array('FOO="foo" BAR="bar"', array(array('FOO', '"foo"'), array('BAR', '"bar"'))),

            "parses FOO='' BAR=bar"
            => array("FOO='' BAR=bar", array(array('FOO', "''"), array('BAR', 'bar'))),

            "parses FOO= BAR=bar"
            => array("FOO= BAR=bar", array(array('FOO', ""), array('BAR', 'bar'))),

            "assigns empty strings"
            => array("FOO= BAR=", array(array('FOO', ""), array('BAR', ''))),

            "parses FOO='foo' BAR=bar"
            => array("FOO='foo' BAR=bar", array(array('FOO', "'foo'"), array('BAR', 'bar'))),

            "parses FOO='foo' BAR='bar'"
            => array("FOO='foo' BAR='bar'", array(array('FOO', "'foo'"), array('BAR', "'bar'"))),

            "parses FOO='foo' BAR=\"bar\""
            => array("FOO='foo' BAR=\"bar\"", array(array('FOO', "'foo'"), array('BAR', '"bar"'))),

            'parses FOO="foo foo" BAR=bar'
            => array('FOO="foo foo" BAR=bar', array(array('FOO', '"foo foo"'), array('BAR', 'bar'))),

            'parses FOO="foo foo" BAR="bar bar"'
            => array('FOO="foo foo" BAR="bar bar"', array(array('FOO', '"foo foo"'), array('BAR', '"bar bar"'))),

            'parses FOO="$var" BAR="bar bar"'
            => array('FOO="$var" BAR="bar bar"', array(array('FOO', '"$var"'), array('BAR', '"bar bar"'))),

            'parses FOO=$var BAR="bar bar"'
            => array('FOO=$var BAR="bar bar"', array(array('FOO', '$var'), array('BAR', '"bar bar"'))),

            'preserves $()'
            => array('FOO=$(command) BAR="bar bar"', array(array('FOO', '$(command)'), array('BAR', '"bar bar"'))),

            'preserves ${NAME}'
            => array('FOO=${NAME} BAR="bar bar"', array(array('FOO', '${NAME}'), array('BAR', '"bar bar"'))),

            'preserves ${NAME}STUFF'
            => array('FOO=${NAME}STUFF BAR="bar bar"', array(array('FOO', '${NAME}STUFF'), array('BAR', '"bar bar"'))),

            'preserves $'
            => array('FOO=$ BAR="bar bar"', array(array('FOO', '$'), array('BAR', '"bar bar"'))),

            'preserves embedded ='
            => array('FOO=comm=bar BAR="bar bar"', array(array('FOO', 'comm=bar'), array('BAR', '"bar bar"'))),

            'ignores unquoted bare word'
            => array('FOO=$comm bar BAR="bar bar"', array(array('FOO', '$comm'), array('BAR', '"bar bar"'))),

            'parses quoted string, with escaped end-quote mark inside'
            => array('FOO="foo\\"bar" BAR="bar bar"', array(array('FOO', '"foo\\"bar"'), array('BAR', '"bar bar"'))),

            'allow $ in the middle'
            => array('APP_URL=http://$APP_HOST:8080 BAR="bar bar"', array(array('APP_URL', 'http://$APP_HOST:8080'), array('BAR', '"bar bar"'))),

            'allow ` in the middle'
            => array('PATH=FOO:`pwd`/bin BAR="bar bar"', array(array('PATH', 'FOO:`pwd`/bin'), array('BAR', '"bar bar"'))),

            '`` with a space inside'
            => array('KERNEL=`uname -r` BAR="bar bar"', array(array('KERNEL', '`uname -r`'), array('BAR', '"bar bar"'))),

            'some stuff, followed by `` with a space inside'
            => array('KERNEL=a`uname -r` BAR="bar bar"', array(array('KERNEL', 'a`uname -r`'), array('BAR', '"bar bar"'))),

            'some stuff, followed by $() with a space inside'
            => array('KERNEL=a$(uname -r) BAR="bar bar"', array(array('KERNEL', 'a$(uname -r)'), array('BAR', '"bar bar"'))),

            'some stuff, followed by "" with a space inside'
            => array('KERNEL=a"$(find \"${TRAVIS_HOME}\" {} \;)" BAR="bar bar"', array(array('KERNEL', 'a"$(find \\"${TRAVIS_HOME}\\" {} \\;)"'), array('BAR', '"bar bar"'))),

            'handle space after the initial $ in ()'
            => array('CAT_VERSION=$(cat VERSION)', array(array('CAT_VERSION', '$(cat VERSION)'))),

            'env var can start with SECURE'
            => array('SECURE_VAR=value BAR="bar bar"', array(array('SECURE_VAR', 'value'), array('BAR', '"bar bar"'))),
        );
    }

    /**
     * @dataProvider provideScanSamples
     *
     * @param string $line
     * @param array $expected
     */
    public function testScan($line, array $expected)
    {
        self::assertSame($expected, EnvVar::scan($line));
    }
}
