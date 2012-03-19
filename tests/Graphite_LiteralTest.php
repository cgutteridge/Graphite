<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_LiteralTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->node = new Graphite_Literal(new Graphite(), null);
    }

    public function testNodeType() {
        $this->assertSame("#literal", $this->node->nodeType());

        $this->markTestIncomplete("Only covers 50% of behaviour");
    }

    public function test() {
        $this->markTestIncomplete('
            function __toString() { return $this->triple["v"]; }
	        function datatype() { return @$this->triple["d"]; }
	        function language() { return @$this->triple["l"]; }

	        function dumpValueText()

	        function dumpValueHTML()
	        function nodeType()

	        function dumpValue()


	        function link() { return $this->__toString(); }
	        function prettyLink() { return $this->__toString(); }'
        );
    }
}
