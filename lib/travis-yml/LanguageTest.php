<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    public function testMatrix()
    {
        $lang = new Language(TravisYml::loadArray(), 'php');
        self::assertSame(TravisYml::$languages['php']['matrix'], $lang->matrixKeys());
    }
}
