<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_ResourceListTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->graph = new Graphite();
        $this->list = new Graphite_ResourceList($this->graph);
    }

    public function testAllOfType() {
        $list = $this->list->allOfType("");

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("I'm clearly misinterpetting how best to test this :/");
/*
        $this->graph->addCompressedTriple("http://foo.com", "rdf:type", "http://bar.com");
        $this->graph->addCompressedTriple("http://foo.com", "foaf:knows", "http://me.com/");


        $this->list->a[] = new Graphite_Resource($this->graph, 'http://foo.com');

        $list = $this->list->allOfType("http://bar.com");

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(1, count($list));
*/
    }

    public function testJoin() {
        $text = $this->list->join("fish");

        $this->assertSame("", $text);

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testDump() {
        $text = $this->list->dump();

        $this->assertSame("", $text);

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testDuplicate() {
        $list = $this->list->duplicate();

        $this->assertNotSame($list, $this->list);
        $this->assertEquals($list, $this->list);

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testSort() {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testUasort() {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testGet() {
        $list = $this->list->get();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testGetLiteral() {
        $list = $this->list->getLiteral();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testGetString() {
        $list = $this->list->getString();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testLabel() {
        $list = $this->list->label();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testLink() {
        $list = $this->list->link();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testPrettyLink() {
        $list = $this->list->prettyLink();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testLoad() {
        $n = $this->list->load();

        $this->assertSame(0, $n);

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testAllString() {
        $list = $this->list->allString();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testAll() {
        $list = $this->list->all();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));

        $this->markTestIncomplete("Needs more coverage");
    }

    public function testAppend() {
        $list = $this->list->append('fish');

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(1, count($list));
        $this->assertContains('fish', $list);

        $this->assertNotSame($list, $this->list);
        $this->assertEquals(array('fish'), (array)$list);


        $list = $this->list->append(array('dogs', 'cats'));

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(2, count($list));
        $this->assertContains('dogs', $list);
        $this->assertContains('cats', $list);
        $this->assertEquals(array('dogs', 'cats'), (array)$list);

        $this->assertNotSame($list, $this->list);



        $list = $this->list->append(new Graphite_ResourceList($this->graph, array('dogs', 'cats', 'dogs')));

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(3, count($list));

        $this->assertContains('dogs', $list);
        $this->assertContains('cats', $list);

        $this->assertEquals(array('dogs', 'cats', 'dogs'), (array)$list);
    }

    public function testDistinct() {
        $list = $this->list->distinct();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));


        $this->list = $this->list->append(array('dogs', 'dogs', 'dogs'));

        $list = $this->list->distinct();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(1, count($list));

        $this->assertEquals(array('dogs'), (array)$list);
    }

    public function testUnion() {
        $list1 = $this->list->append(array('dogs', 'dogs', 'dogs'));
        $list2 = $this->list->append(array('cats', 'cats', 'cats'));

        $list3 = $list1->union($list2);

        $this->assertEquals(array('dogs', 'cats'), (array)$list3);
    }

    public function testIntersection() {
        $list1 = $this->list->append(array('dogs', 'dogs', 'dogs'));
        $list2 = $this->list->append(array('cats', 'dogs', 'cats'));

        $list3 = $list1->intersection($list2);

        $this->assertEquals(array('dogs'), (array)$list3);
    }

    public function testExcept() {
        $list1 = $this->list->append(array('cats', 'dogs', 'cats'));
        $list2 = $this->list->append(array('dogs', 'dogs', 'dogs'));

        $list3 = $list1->except($list2);

        $this->assertEquals(array(new Graphite_Resource($this->graph, 'cats'),
                                  new Graphite_Resource($this->graph, 'cats')),
            (array)$list3
        );
    }

}
