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
        $lang = new Language('php');
        $expected = Language::$languages['php']['matrix'];
        $expected[] = 'env';
        self::assertSame($expected, $lang->matrixKeys());
    }
}
