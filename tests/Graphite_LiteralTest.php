<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_LiteralTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->node = new Graphite_Literal(new Graphite(), null);
    }

    public function testNodeType() {
        $this->assertSame("#literal", $this->node->nodeType());

        $this->node->triple = array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US');

        $this->assertSame("#fish", $this->node->nodeType());
    }

    public function test__toString() {
        $this->assertSame("", (string)$this->node);

        $this->node->triple = array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US');

        $this->assertSame("hi", (string)$this->node);
    }

    public function testLink() {
        $this->assertSame("", $this->node->link());

        $this->node->triple = array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US');

        $this->assertSame("hi", $this->node->link());
    }

    public function testPrettyLink() {
        $this->assertSame("", $this->node->prettyLink());

        $this->node->triple = array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US');

        $this->assertSame("hi", $this->node->prettyLink());
    }


    public function testDatatype() {
        $this->assertSame(null, $this->node->datatype());
        $this->node->triple = array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US');

        $this->assertSame("#fish", $this->node->datatype());
    }

    public function testLanguage() {
        $this->assertSame(null, $this->node->language());

        $this->node->triple = array('v' => 'hi', 'd' => '#fish', 'l' => 'en-US');

        $this->assertSame("en-US", $this->node->language());
    }

    public function test() {
        $this->markTestIncomplete('
	        function dumpValueText()
            function dumpValueHTML()
	        function dumpValue()


	        function link() { return $this->__toString(); }
	        function prettyLink() { return $this->__toString(); }'
        );
    }
}
