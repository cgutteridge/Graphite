<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_RelationTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->node = new Graphite_Relation(new Graphite(), null);
    }

    public function testNodeType() {
        $this->assertSame("#relation", $this->node->nodeType());
    }
}
