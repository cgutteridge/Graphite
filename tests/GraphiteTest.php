<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->graph = new Graphite();
    }

    public function test() {
        $this->markTestIncomplete('
	public function __construct( $namespaces = array(), $uri = null )
	public function freeze( $filename )
	public static function thaw( $filename )
	public static function __set_state($data) // As of PHP 5.1.0
	public function cacheDir( $dir, $age = 86400 ) # default age is 24 hours
	public function setDebug( $boolean ) { $this->debug = $boolean; }
	public function labelRelations( $new = null )
	public function addLabelRelation( $addition )
	public function mailtoIcon( $new = null )
	public function telIcon( $new = null )
	function removeFragment( $uri )
	function loaded( $uri )
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
	public function resource( $uri )
	public function allOfType( $uri )
	public function shrinkURI( $uri )
	public function expandURI( $uri )
	public function allSubjects()
	public function allObjects()
	public function dump( $options=array() )
	public function dumpText( $options=array() )
	public function forceString( &$uri )
    ');
    }
}
