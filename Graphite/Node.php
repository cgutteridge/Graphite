<?php
class Graphite_Node
{
	function __construct(Graphite $g )
	{
		$this->g = $g;
	}
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
	function dump() { return "<div style='padding:0.5em; background-color:lightgrey;border:dashed 1px grey;'>Non-existant Node</div>"; }
	function nodeType() { return "#node"; }
	function __toString() { return "[NULL]"; }
	function toString() { return $this->__toString(); }
	function datatype() { return null; } 
	function language() { return null; } 


	protected function parsePropertyArg( $arg )
	{
		if( is_a( $arg, "Graphite_Resource" ) )
		{
			if( is_a( $arg, "Graphite_InverseRelation" ) )
			{
				$this->g->forceString( $arg );
				return array( "op", "$arg" );
			}
			$this->g->forceString( $arg );
			return array( "sp", "$arg" );
		}

		$set = "sp";
		if( substr( $arg,0,1) == "-" )
		{
			$set = "op";
			$arg = substr($arg,1);
		}
		return array( $set, $this->g->expandURI( "$arg" ) );
	}
}
