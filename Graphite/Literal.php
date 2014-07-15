<?php
class Graphite_Literal extends Graphite_Node
{
	function __construct(Graphite $g, $triple )
	{
		$this->g = $g;
		$this->setTriple($triple);
	}

	/**
	 * Modify the triple / value represented by this instance
	 *
	 * @param array $triple
	 */
	public function setTriple($triple) {
		$this->triple = $triple;
		$this->v = $triple["v"];
	}

	function __toString() {
		return isset($this->triple["v"]) ? Graphite::asString($this->triple['v']) : "";
	}
	function datatype() { return @$this->triple["d"]; }
	function language() { return @$this->triple["l"]; }

	function dumpValueText()
	{
		$r = '"'.$this->v.'"';
		if( isset($this->triple["l"]) && $this->triple["l"])
		{
			$r.="@".$this->triple["l"];
		}
		if( isset($this->triple["d"]) )
		{
			$r.="^^".$this->g->shrinkURI($this->triple["d"]);
		}
		return $r;
	}

	function dumpValueHTML()
	{
		$v = htmlspecialchars( $this->triple["v"],ENT_COMPAT,"UTF-8" );

		$v = preg_replace( "/\t/", "<span class='special_char' style='font-size:70%'>[tab]</span>", $v );
		$v = preg_replace( "/\n/", "<span class='special_char' style='font-size:70%'>[nl]</span><br />", $v );
		$v = preg_replace( "/\r/", "<span class='special_char' style='font-size:70%'>[cr]</span>", $v );
		$v = preg_replace_callback( "/  +/", function($matches) {
			return "<span class='special_char' style='font-size:70%'>".str_repeat("␣",strlen($matches[0]))."</span>";
		}, $v );
		$r = '"'.$v.'"';

		if( isset($this->triple["l"]) && $this->triple["l"])
		{
			$r.="@".$this->triple["l"];
		}
		if( isset($this->triple["d"]) )
		{
			$r.="^^".$this->g->shrinkURI($this->triple["d"]);
		}
		return $r;
	}

	function nodeType()
	{
		if( isset($this->triple["d"]) )
		{
			return $this->triple["d"];
		}
		return "#literal";
	}

	function dumpValue()
	{
		return "<span style='color:blue'>".$this->dumpValueHTML()."</span>";
	}

	function link() { return $this->__toString(); }
	function prettyLink() { return $this->__toString(); }
}
