<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_ResourceTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->resource = new Graphite_Resource(new Graphite(), null);
    }

    public function testPrepareDescription() {
        $description = $this->resource->prepareDescription();

        $this->assertTrue($description instanceof Graphite_Description);
    }

    public function testDumpValueText() {
        $this->assertSame("* This Document *", $this->resource->dumpValueText());
    }

    public function testDumpValue() {
        $this->assertSame("<a href='' title='' style='text-decoration:none;color:red'>* This Document *</a>", $this->resource->dumpValue());

        $this->resource->uri = 'http://whee.com';

        $this->assertSame("<a href='http://whee.com' title='http://whee.com' style='text-decoration:none;color:red'>http://whee.com</a>", $this->resource->dumpValue());

        $this->assertSame("<a href='#http://whee.com' title='http://whee.com' style='text-decoration:none;color:red'>http://whee.com</a>", $this->resource->dumpValue(array('internallinks' => true)));
    }

    public function testNodeType() {
        $this->assertSame("#resource", $this->resource->nodeType());
    }


    public function test__toString() {
        $this->assertSame("", (string)$this->resource);

        $this->resource->uri = 'http://bob.com';

        $this->assertSame('http://bob.com', (string)$this->resource);
    }

    public function testDump() {
        //<a name=''></a><div style='text-align:left;font-family: arial;padding:0.5em; background-color:lightgrey;border:dashed 1px grey;margin-bottom:2px;'>\n <!-- DUMP: -->\n <div><a title='' href='' style='text-decoration:none'>* This Document *</a></div>\n<div style='padding-left: 3em'>\n<div></div></div><div style='clear:both;height:1px; overflow: hidden'>&nbsp;</div></div>
        $this->assertContains("<a title='' href='' style='text-decoration:none'>* This Document *</a>", $this->resource->dump());
        //$this->assertSame("", $this->resource->dump(array('label' => true)));

        $this->markTestIncomplete("Needs further coverage");
        $this->markTestIncomplete("Test is way too fragile");
    }

    public function testDumpText() {
        $this->assertContains("* This Document *", $this->resource->dumpText());

        $this->markTestIncomplete("Needs further coverage");
        $this->markTestIncomplete("Test is too fragile");
    }

    public function testPrettyLink() {
        $this->assertSame("<a title='' href=''></a>", $this->resource->prettyLink());

        $this->resource->uri = 'http://bob.com';
        $this->assertSame("<a title='http://bob.com' href='http://bob.com'>http://bob.com</a>", $this->resource->prettyLink());

        $this->resource->uri = 'tel:+61-0543-34534';
        $this->resource->g->telIcon("http://example.com/BOB.jpg");
        $phone_link = $this->resource->prettyLink();

        $this->assertSame("<span style='white-space:nowrap'><a title='tel:+61-0543-34534' href='tel:+61-0543-34534'><img style='padding-right:0.2em;' src='http://example.com/BOB.jpg' /></a><a title='tel:+61-0543-34534' href='tel:+61-0543-34534'>+61-0543-34534</a></span>", $phone_link);

        $this->resource->uri = 'mailto:you@example.com';
        $this->resource->g->mailtoIcon("http://example.com/BOB.jpg");
        $this->assertSame("<span style='white-space:nowrap'><a title='mailto:you@example.com' href='mailto:you@example.com'><img style='padding-right:0.2em;' src='http://example.com/BOB.jpg' /></a><a title='mailto:you@example.com' href='mailto:you@example.com'>you@example.com</a></span>", $this->resource->prettyLink());
    }


    public function testLink() {
        $this->assertSame("<a title='' href=''></a>", $this->resource->link());

        $this->resource->uri = 'http://bob.com';

        $this->assertSame("<a title='http://bob.com' href='http://bob.com'>http://bob.com</a>", $this->resource->link());
    }

    public function testLabel() {
        $this->assertInstanceOf('Graphite_Null', $this->resource->label());

        $this->markTestIncomplete("Needs further coverage");
    }


    public function testIsType() {
        $this->assertFalse($this->resource->isType());
        $this->assertFalse($this->resource->isType(null));

        $this->markTestIncomplete("Needs further coverage");
    }


    public function testType() {
        $type = $this->resource->type();
        $this->assertTrue($type instanceof Graphite_Null);

        $this->markTestIncomplete("Needs further coverage");
    }

    public function testTypes() {
        $types = $this->resource->types();

        $this->assertTrue($types instanceof Graphite_ResourceList);
        $this->assertSame(0, count($types));

        $this->markTestIncomplete("Needs further coverage");
    }


    public function testAll() {
        $all = $this->resource->all();

        $this->assertTrue($all instanceof Graphite_ResourceList);
        $this->assertSame(0, count($all));
    }


    public function testLoad() {
        $this->assertSame(0, $this->resource->load());
    }

    public function testLoadSameAs() {
        $this->assertSame(0, $this->resource->loadSameAs(null));
    }

    public function testLoadSameAsOrg1() {
        $this->assertSame(0, $this->resource->loadSameAsOrg(null));
    }

    public function testLoadSameAsOrg2() {
        $retriever = $this->getMock('Graphite_Retriever', array(), array($this->resource->g));
        $this->resource->g->setRetriever($retriever);

        $retriever->expects($this->once())
                    ->method('retrieve')
                    ->with('http://sameas.org/rdf?uri=' . urlencode('http://bob.com'))
                    ->will($this->returnValue(
                            file_get_contents(dirname(__FILE__) . '/data/sameas.org.rdf')
                    ));

        $this->resource->uri = 'http://bob.com';

        $this->assertSame(151, $this->resource->loadSameAsOrg(null));
    }

    public function testLoadDataGovUKBackLinks() {
        $this->assertSame(0, $this->resource->loadDataGovUKBackLinks());
    }

    public function testToArcTriples() {
        $this->assertSame(array(), $this->resource->toArcTriples());

        $this->resource->g->addTriple('http://my.com/dog#', '#smells', 'http://wikipedioa.org/Terribly#');

        $this->resource->uri = 'http://my.com/dog#';

        $this->assertSame(array(0 => array(
            's' => 'http://my.com/dog#',
            's_type' => 'uri',
            'p' => '#smells',
            'p_type' => 'uri',
            'o' => 'http://wikipedioa.org/Terribly#',
            'o_type' => 'uri',
            'o_datatype' =>  null,
            'o_lang' => null,
        )), $this->resource->toArcTriples());


        $this->assertSame(array(0 => array(
            's' => 'http://my.com/dog#',
            's_type' => 'uri',
            'p' => '#smells',
            'p_type' => 'uri',
            'o' => 'http://wikipedioa.org/Terribly#',
            'o_type' => 'uri',
            'o_datatype' =>  null,
            'o_lang' => null,
        )), $this->resource->toArcTriples(true));
    }

    public function testRelations() {
        $relations = $this->resource->relations();

        $this->assertTrue($relations instanceof Graphite_ResourceList);
        $this->assertSame(0, count($relations));

        $this->resource->g->addTriple('http://my.com/dog#', '#smells', 'http://wikipedioa.org/Terribly#');

        $this->resource->uri = 'http://wikipedioa.org/Terribly#';

        $relations = $this->resource->relations();
        $this->assertTrue($relations instanceof Graphite_ResourceList);
        $this->assertSame(1, count($relations));
        $this->assertTrue($relations[0] instanceof Graphite_InverseRelation, get_class($relations[0]));

        $this->resource->uri = 'http://my.com/dog#';
        $relations = $this->resource->relations();
        $this->assertTrue($relations instanceof Graphite_ResourceList);
        $this->assertSame(1, count($relations));
        $this->assertTrue($relations[0] instanceof Graphite_Relation, get_class($relations[0]));

        $this->resource->g->addTriple('I', 'dislike', 'http://my.com/dog#');

        $relations = $this->resource->relations();
        $this->assertTrue($relations instanceof Graphite_ResourceList);
        $this->assertSame(2, count($relations));
        $this->assertTrue($relations[0] instanceof Graphite_Relation, get_class($relations[0]));
        $this->assertTrue($relations[1] instanceof Graphite_InverseRelation, get_class($relations[1]));

        $this->markTestIncomplete("Needs further coverage");
    }
}
