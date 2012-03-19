<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';

class Graphite_InverseRelationTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->node = new Graphite_InverseRelation();
    }

    public function testNodeType() {
        $this->assertSame("#inverseRelation", $this->node->nodeType());
    }
}
