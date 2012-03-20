<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_ResourceTest extends PHPUnit_Framework_TestCase {

    public function test() {
        $this->markTestIncomplete('	function __construct( $g, $uri )

	public function get( /* List */ )

	public function getLiteral( /* List */ )
	# getString deprecated in favour of getLiteral
	public function getString( /* List */ ) { return $this->getLiteral( func_get_args() ); }

	public function getDatatype( /* List */ )
	public function getLanguage( /* List */ )

	public function allString( /* List */ )
	public function has(  /* List */ )
	public function all(  /* List */ )
	public function relations()
	public function toArcTriples( $bnodes = true )
	public function serialize( $type = "RDFXML" )
	public function load()
	public function loadSameAsOrg( $prefix )
	function loadDataGovUKBackLinks()
	public function loadSameAs( $prefix=null )
	public function type()
	public function types()
	public function isType( /* List */ )
	public function hasLabel()
	public function label()
	function __toString() { return $this->uri; }
	function dumpValue($options=array())
	function dumpValueText() { return $this->g->shrinkURI( $this->uri ); }
	function nodeType() { return "#resource"; }

	function prepareDescription()');
    }

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
    }

    public function testDump() {
        $this->assertSame("<a name=''></a><div style='text-align:left;font-family: arial;", $this->resource->dump());
        $this->assertSame("", $this->resource->dump(array('label' => true)));

        $this->markTestIncomplete("Needs further coverage");
    }

    public function testDumpText() {
        $this->assertSame("* This Document *\n   .\n", $this->resource->dumpText());

        $this->markTestIncomplete("Needs further coverage");
    }

    public function testPrettyLink() {
        $this->assertSame("<a title='' href=''></a>", $this->resource->prettyLink());

        $this->markTestIncomplete("Needs further coverage");
    }


    public function testLink() {
        $this->assertSame("<a title='' href=''></a>", $this->resource->link());

        $this->markTestIncomplete("Needs further coverage");
    }

    public function testLabel() {
        $this->assertSame("", $this->resource->label());

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

    public function testLoadSameAsOrg() {
        $this->assertSame(0, $this->resource->loadSameAsOrg(null));
    }

    public function testLoadDataGovUKBackLinks() {
        $this->assertSame(0, $this->resource->loadDataGovUKBackLinks());
    }

    public function testToArcTriples() {
        $this->assertSame(array(), $this->resource->toArcTriples());

        $this->resource->g->addTriple(array('s' => 'http://my.com/dog#', 'p' => '#smells', 'o' => 'http://wikipedioa.org/Terribly#'));

        $this->uri = 'http://my.com/dog#';
        $this->assertSame(array(), $this->resource->toArcTriples());


        $this->assertSame(array(), $this->resource->toArcTriples(true));
    }
}
