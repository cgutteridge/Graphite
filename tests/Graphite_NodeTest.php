<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';

class Graphite_NodeTest extends  PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->node = new Graphite_Node(new Graphite());
    }


    public function testIsNull() {
        $this->assertFalse($this->node->isNull());
    }

    public function testHas() {
        $this->assertFalse($this->node->has());
    }

    public function testGet() {
        $node = $this->node->get();

        $this->assertTrue($node instanceof Graphite_Null);
    }

    public function testType() {
        $node = $this->node->type();

        $this->assertTrue($node instanceof Graphite_Null);
    }

    public function testHasLabel() {
        $this->assertFalse($this->node->hasLabel());
    }

    public function testLabel() {
        $this->assertSame("[UNKNOWN]", $this->node->label());
    }

    public function testAll() {
        $all = $this->node->all();

        $this->assertTrue($all instanceof Graphite_ResourceList);
        $this->assertSame(0, count($all));
    }

    public function testTypes() {
        $all = $this->node->types();

        $this->assertTrue($all instanceof Graphite_ResourceList);
        $this->assertSame(0, count($all));
    }

    public function testRelations() {
        $all = $this->node->relations();

        $this->assertTrue($all instanceof Graphite_ResourceList);
        $this->assertSame(0, count($all));
    }

    public function testLoad() {
        $this->assertSame(0, $this->node->load());
    }

    public function testLoadSameAs() {
        $this->assertSame(0, $this->node->loadSameAs(null));
    }

    public function testLoadSameAsOrg() {
        $this->assertSame(0, $this->node->loadSameAsOrg(null));
    }

    public function testLoadDataGovUKBackLinks() {
        $this->assertSame(0, $this->node->loadDataGovUKBackLinks());
    }

    public function testDumpText() {
        $this->assertSame("Non existant Node", $this->node->dumpText());
    }

    public function testDump() {
        $this->assertSame("<div style='padding:0.5em; background-color:lightgrey;border:dashed 1px grey;'>Non-existant Node</div>", $this->node->dump());
    }

    public function testNodeType() {
        $this->assertSame("#node", $this->node->nodeType());
    }

    public function test__toString() {
        $this->assertSame("[NULL]", (string)$this->node);
    }


    public function testDataType() {
        $this->assertSame(null, $this->node->dataType());
    }


    public function testLanguage() {
        $this->assertSame(null, $this->node->language());
    }

    public function test() {
        $this->markTestIncomplete('
	        protected function parsePropertyArg( $arg )');
    }
}
