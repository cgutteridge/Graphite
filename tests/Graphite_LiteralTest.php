<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_LiteralTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->node = new Graphite_Literal(new Graphite(), null);
    }

    public function testNodeType() {
        $this->assertSame("#literal", $this->node->nodeType());

        $this->node->setTriple(array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US'));

        $this->assertSame("#fish", $this->node->nodeType());
    }

    public function test__toString() {
        $this->assertSame("", (string)$this->node);

        $this->node->setTriple(array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US'));

        $this->assertSame("hi", (string)$this->node);

        $this->node->setTriple(array('v' => '0', 'd' => '#fish', 'l' => 'en-US'));

        $this->assertSame("0", (string)$this->node);
    }

    public function testLink() {
        $this->assertSame("", $this->node->link());

        $this->node->setTriple(array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US'));

        $this->assertSame("hi", $this->node->link());
    }

    public function testPrettyLink() {
        $this->assertSame("", $this->node->prettyLink());

        $this->node->setTriple(array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US'));

        $this->assertSame("hi", $this->node->prettyLink());
    }


    public function testDatatype() {
        $this->assertSame(null, $this->node->datatype());
        $this->node->setTriple(array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US'));

        $this->assertSame("#fish", $this->node->datatype());
    }

    public function testLanguage() {
        $this->assertSame(null, $this->node->language());

        $this->node->setTriple(array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US'));

        $this->assertSame("en-US", $this->node->language());
    }

    public function testDumpValue() {
        $this->assertSame("<span style='color:blue'>\"\"</span>", $this->node->dumpValue());
    }

    public function testDumpValueText() {
        $this->assertSame('""', $this->node->dumpValueText());
    }

    public function testDumpValueHTML() {
        $this->assertSame('""', $this->node->dumpValueHTML());
    }
}
