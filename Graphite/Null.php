<?php
class Graphite_Null extends Graphite_Node
{
	function nodeType() { return "#null"; }
	function isNull() { return true; }
}
