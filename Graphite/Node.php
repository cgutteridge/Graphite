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

    /** @deprecated Use __toString() or (string) instead */
	function toString() { return $this->__toString(); }
	function datatype() { return null; }
	function language() { return null; }

}
