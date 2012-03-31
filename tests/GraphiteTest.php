<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->graph = new Graphite();
    }

    public function testSetDebug() {
        $this->assertFalse($this->graph->debug);

        $this->graph->setDebug(true);

        $this->assertTrue($this->graph->debug);
    }

    public function testLoaded() {
        $this->assertFalse($this->graph->loaded(null));
    }

    public function testToArcTriples() {
        $this->assertSame(array(), $this->graph->toArcTriples());
    }

    public function testResource() {
        $resource = $this->graph->resource(null);

        $this->assertTrue($resource instanceof Graphite_Resource);
    }

    public function testExpandURI() {
        $this->assertSame('http://xmlns.com/foaf/0.1/knows', $this->graph->expandURI('foaf:knows'));
    }

    public function testShrinkURI() {
        $this->assertSame("* This Document *", $this->graph->shrinkURI(null));

        $this->assertSame('foaf:knows', $this->graph->shrinkURI('http://xmlns.com/foaf/0.1/knows'));

        $this->assertSame('http://xmlns.com/foaf/0.2/knows', $this->graph->shrinkURI('http://xmlns.com/foaf/0.2/knows'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A valid xmlns prefix is required. 
     */
    public function testNsRequiresValidInput1() {
        $this->graph->ns(null, null);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Setting a namespace called 'urn' is just asking for trouble. Abort.
     */
    public function testNsProbhitsRedefiningCoreNamespaces() {
        $this->graph->ns('urn', 'whee');
    }


    public function testNs() {
        $this->graph->ns('fish', 'pants');

        $this->assertContains('pants', $this->graph->ns);
    }

    public function testCleanURI() {
        $this->assertSame(null, $this->graph->cleanURI(null));

        $this->assertSame('http://google.com/pish/pash#20:80', $this->graph->cleanURI('http://google.com:80/pish/pash#20:80'));

        $this->assertSame('file:///odd/uri/scheme', $this->graph->cleanURI('file:///odd/uri/scheme'));
    }

    public function testAllSubjectsWithEmptyGraph() {
        $list = $this->graph->allSubjects();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(0, count($list));
    }


    public function testAddTriple() {
        $this->graph->addTriple('http://my.com/cat#', "http://smells.com/nose_quanity", "Zero");

        // Subject predicate object relation stored?
        $this->assertTrue(array_key_exists(
            'http://my.com/cat#',
            $this->graph->t["sp"]
        ));

        $this->assertTrue(array_key_exists(
            'http://smells.com/nose_quanity',
            $this->graph->t["sp"]['http://my.com/cat#']
        ));

        $this->assertSame(
            array(0 => "Zero"),           
            $this->graph->t["sp"]['http://my.com/cat#']['http://smells.com/nose_quanity']
        );

        // object predicate subject relation stored?
        // TODO: Think about this or literal values of int(0) which are type cast to string ''
        $this->assertTrue(array_key_exists(
            'Zero',
            $this->graph->t["op"]
        ));

        $this->assertTrue(array_key_exists(
            'http://smells.com/nose_quanity',
            $this->graph->t["op"]['Zero']
        ));

        $this->assertSame(
            array(0 => "http://my.com/cat#"),           
            $this->graph->t["op"]['Zero']['http://smells.com/nose_quanity']
        );

    }

    public function testAllSubjectsWithTriples() {
        $this->graph->addTriple('http://my.com/cat#', "http://smells.com/nose_quanity", "Zero");
        $this->graph->addTriple('http://you.com/#', "http://question.com/question", "http://question.com/1#");
        $this->graph->addTriple('http://question.com/1#', "dc:title", "How does he smell?");
        $this->graph->addTriple('http://my.com/cat#', "http://smells.com/smells", "Terribly");

        $list = $this->graph->allSubjects();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(3, count($list));

        $this->assertTrue($list[0] instanceof Graphite_Resource);
        $this->assertTrue($list[1] instanceof Graphite_Resource);
        $this->assertTrue($list[2] instanceof Graphite_Resource);

        $this->assertSame("http://my.com/cat#", $list[0]->uri);
        $this->assertSame("http://you.com/#", $list[1]->uri);
        $this->assertSame("http://question.com/1#", $list[2]->uri);
    }

    public function testAllObjectsWithTriples() {
        $this->graph->addTriple('http://my.com/cat#', "http://smells.com/nose_quanity", "Zero");
        $this->graph->addTriple('http://you.com/#', "http://question.com/question", "http://question.com/1#");
        $this->graph->addTriple('http://question.com/1#', "dc:title", "How does he smell?");
        $this->graph->addTriple('http://my.com/cat#', "http://smells.com/smells", "Terribly");

        $list = $this->graph->allObjects();

        $this->assertTrue($list instanceof Graphite_ResourceList);
        $this->assertSame(4, count($list));

        $this->assertTrue($list[0] instanceof Graphite_Resource);
        $this->assertTrue($list[1] instanceof Graphite_Resource);
        $this->assertTrue($list[2] instanceof Graphite_Resource);
        $this->assertTrue($list[3] instanceof Graphite_Resource);

        $this->assertSame("Zero", $list[0]->uri);
        $this->assertSame("http://question.com/1#", $list[1]->uri);
        $this->assertSame("How does he smell?", $list[2]->uri);
        $this->assertSame("Terribly", $list[3]->uri);
    }

    public function test() {
        $this->markTestIncomplete('
	public function __construct( $namespaces = array(), $uri = null )
	public function freeze( $filename )
	public static function thaw( $filename )
	public static function __set_state($data) // As of PHP 5.1.0
	public function cacheDir( $dir, $age = 86400 ) # default age is 24 hours
	public function labelRelations( $new = null )
	public function addLabelRelation( $addition )
	public function mailtoIcon( $new = null )
	public function telIcon( $new = null )
	function removeFragment( $uri )
	public function load( $uri, $aliases = array(), $map = array() )
	function loadSPARQL( $endpoint, $query )
	function addTurtle( $base, $data )
	function addRDFXML( $base, $data )
	function addBnodePrefix( $uri ) 
	function addTriples( $triples, $aliases = array(), $map = array() )
	function addCompressedTriple( $s,$p,$o,$o_datatype=null,$o_lang=null,$aliases=array() )
	function addTriple( $s,$p,$o,$o_datatype=null,$o_lang=null,$aliases=array() )
	public function toArcTriples()
	public function serialize( $type = "RDFXML" )
	public function primaryTopic( $uri = null )
	public function allOfType( $uri )
	public function allObjects()
	public function dump( $options=array() )
	public function dumpText( $options=array() )
    ');
    }
}
