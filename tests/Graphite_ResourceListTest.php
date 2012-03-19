<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';


class Graphite_ResourceListTest extends PHPUnit_Framework_TestCase {

    public function test() {
        $this->markTestIncomplete('
	function __construct( $g, $a=array() )
	function join( $str )
	function dump()
	public function duplicate()
	public function sort( /* List */ )
	public function uasort( $cmp )
	public function get( /* List */ )
	
	public function getLiteral( /* List */)
	# getString deprecated in favour of getLiteral 
	public function getString( /* List */ ) { return $this->getLiteral( func_get_args() ); }

	public function label()
	public function link() 
	public function prettyLink() 
	public function load()
	public function allString( /* List */ )

	public function all( /* List */ )
	function append( $x /* List */ )

	function distinct()
	function union( /* List */ )
	function intersection( /* List */ )
	function except( /* List */ )


	function allOfType( $uri )
    ');
}
}
