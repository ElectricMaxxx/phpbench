<?php

/*
 * This file is part of the PHPBench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Tests\Unit\Serializer;

use PhpBench\Serializer\XmlEncoder;

class XmlEncoderTest extends XmlTestCase
{
    /**
     * It should encode the suite to an XML document.
     *
     * @dataProvider provideEncode
     */
    public function testEncode(array $params, $expected)
    {
        $collection = $this->getSuiteCollection($params);
        $xmlEncoder = new XmlEncoder();
        $dom = $xmlEncoder->encode($collection);
        $this->assertInstanceOf('PhpBench\Dom\SuiteDocument', $dom);
        $this->assertEquals($expected, $dom->dump());
    }
}