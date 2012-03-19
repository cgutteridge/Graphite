<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';

class Graphite_NullTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->node = new Graphite_Null(new Graphite());
    }

    public function testNodeType() {
        $this->assertSame("#null", $this->node->nodeType());
    }

    public function testIsNull() {
        $this->assertTrue($this->node->isNull());
    }
}
