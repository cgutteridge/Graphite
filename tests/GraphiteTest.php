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
    }

    /** @expectedException InvalidArgumentException */
    public function testNs1() {
        $this->graph->ns(null, null);
    }
   
    /** @expectedException InvalidArgumentException */
    public function testNs2() {
        $this->graph->ns('urn', 'whee');
    }


    public function testNs3() {
        $this->graph->ns('fish', 'pants');

        $this->assertContains('pants', $this->graph->ns);
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
	public function cleanURI( $uri )
	public function primaryTopic( $uri = null )
	public function ns( $short, $long )
	public function allOfType( $uri )
	public function allSubjects()
	public function allObjects()
	public function dump( $options=array() )
	public function dumpText( $options=array() )
	public function forceString( &$uri )
    ');
    }
}
