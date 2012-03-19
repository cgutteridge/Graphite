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
	public function link()
	public function prettyLink()
	public function dumpText()
	public function dump( $options = array() )
	function __toString() { return $this->uri; }
	function dumpValue($options=array())
	function dumpValueText() { return $this->g->shrinkURI( $this->uri ); }
	function nodeType() { return "#resource"; }

	function prepareDescription()');
    }
}
