<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

use PHPUnit\Framework\TestCase;

class ArgsTest extends TestCase
{
    public function testParseLater()
    {
        $subject = new Args(array('foo', '-e', 'eval', '-b', '-f', 'file'));
        $subject->optFlag(array('-b'), $b);
        self::assertFalse($b);
        $subject->optArg(array('-f'), $file);
        self::assertNull($file);
        $subject->optArg(array('-e'), $eval);
        self::assertSame('eval', $eval);
        self::assertSame('file', $file);
        self::assertTrue($b);
    }

    public function testInjectEnv()
    {
        $env = array('FOO' => 'BAR');

        $subject = new Args(array(), Args::environ($env));
        $subject->env('FOO', $argument);
        self::assertSame('BAR', $argument);
        $subject->env('BAR', $bar, 'BAZ');
        self::assertSame('BAZ', $bar);
        $subject->env('BAR', $argument);
        self::assertSame('BAR', $argument, 'previous argument is not set to default with no default');
        $subject->env('QUX', $qux);
        self::assertNull($qux);
        $subject->env('QUX', $argument, null);
        self::assertNull($argument);
    }
}
