<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';

class Graphite_NodeTest extends  PHPUnit_Framework_TestCase {
    public function test() {
        $this->markTestIncomplete('
	        function isNull() { return false; }
	        function has() { return false; }
	        function get() { return new Graphite_Null($this->g); }
	        function type() { return new Graphite_Null($this->g); }
	        function label() { return "[UNKNOWN]"; }
	        function hasLabel() { return false; }
	        function all() { return new Graphite_ResourceList($this->g, array()); }
	        function types() { return $this->all(); }
	        function relations() { return $this->all(); }
	        function load() { return 0; }
	        function loadSameAs() { return 0; }
	        function loadSameAsOrg($prefix) { return 0; }
	        function loadDataGovUKBackLinks() { return 0; }

	        function dumpText() { return "Non existant Node"; }
	        function dump() { return "<div style=padding:0.5em; background-color:lightgrey;border:dashed 1px grey;>Non-existant Node</div>"; }
	        function nodeType() { return "#node"; }
	        function __toString() { return "[NULL]"; }
	        function toString() { return $this->__toString(); }
	        function datatype() { return null; } 
	        function language() { return null; } 


	        protected function parsePropertyArg( $arg )');
    }
}
