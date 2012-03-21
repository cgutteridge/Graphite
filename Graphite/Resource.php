<?php
class Graphite_Resource extends Graphite_Node
{
	function __construct(Graphite $g, $uri )
	{
		$this->g = $g;
		$this->g->forceString( $uri );
		$this->uri = $uri;
	}

	public function get( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return new Graphite_Null($this->g); }
		return $l[0];
	}

	public function getLiteral( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return; }
		return $l[0]->toString();
	}
	# getString deprecated in favour of getLiteral
	public function getString( /* List */ ) { return $this->getLiteral( func_get_args() ); }

	public function getDatatype( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return; }
		return $l[0]->datatype();
	}
	public function getLanguage( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return; }
		return $l[0]->language();
	}

	public function allString( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = array();
		foreach( $this->all( $args ) as $item )
		{
			$l []= $item->toString();
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	public function has(  /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		foreach( $args as $arg )
		{
			list( $set, $relation_uri ) = $this->parsePropertyArg( $arg );
			if( isset($this->g->t[$set][$this->uri])
			 && isset($this->g->t[$set][$this->uri][$relation_uri]) )
			{
				return true;
			}
		}
		return false;
	}

	public function all(  /* List */ )
	{
		$args = func_get_args();

		if (empty($args)) {
			return new Graphite_ResourceList($this->g, array());
		}

		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }
		$l = array();
		$done = array();
		foreach( $args as $arg )
		{
			list( $set, $relation_uri ) = $this->parsePropertyArg( $arg );
			if( !isset($this->g->t[$set][$this->uri])
			 || !isset($this->g->t[$set][$this->uri][$relation_uri]) )
			{
				continue;
			}

			foreach( $this->g->t[$set][$this->uri][$relation_uri] as $v )
			{
				if( is_array( $v ) )
				{
					$l []= new Graphite_Literal( $this->g, $v );
				}
				else if( !isset($done[$v]) )
				{
					$l []= new Graphite_Resource( $this->g, $v );
					$done[$v] = 1;
				}
			}
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	public function relations()
	{
		$r = array();
		if( isset( $this->g->t["sp"][$this->uri] ) )
		{
			foreach( array_keys( $this->g->t["sp"][$this->uri] ) as $pred )
			{
				$r []= new Graphite_Relation( $this->g, $pred );
			}
		}
		if( isset( $this->g->t["op"][$this->uri] ) )
		{
			foreach( array_keys( $this->g->t["op"][$this->uri] ) as $pred )
			{
				$r []= new Graphite_InverseRelation( $this->g, $pred );
			}
		}

		return new Graphite_ResourceList($this->g,$r);
	}

	public function toArcTriples( $bnodes = true )
	{
		$arcTriples = array();
		$bnodes_to_add = array();

		$s = $this->uri;
		$s_type = "uri";
		if( preg_match( '/^_:/', $s ) )
		{
			$s_type = "bnode";
		}

		if (!empty($this->g->t["sp"][$s])) {

			foreach( $this->g->t["sp"][$s] as $p => $os )
			{
				$p = $this->g->expandURI( $p );
				$p_type = "uri";
				if( preg_match( '/^_:/', $p ) )
				{
					$p_type = "bnode";
				}

				foreach( $os as $o )
				{
					$o_lang = null;
					$o_datatype = null;
					if( is_array( $o ))
					{
						$o_type = "literal";
						if( isset( $o["l"] ) && $o["l"] )
						{
							$o_lang = $o["l"];
						}
						if( isset( $o["d"] ) )
						{
							$o_datatype = $this->g->expandURI( $o["d"] );
						}
						$o = $o["v"];
					}
					else
					{
						$o = $this->g->expandURI( $o );
						$o_type = "uri";
						if( preg_match( '/^_:/', $o ) )
						{
							$o_type = "bnode";
							$bnodes_to_add[] = $o;
						}
					}
					$triple = array(
						"s" => $s,
						"s_type" => $s_type,
						"p" => $p,
						"p_type" => $p_type,
						"o" => $o,
						"o_type" => $o_type,
					);
					$triple["o_datatype"] = $o_datatype;
					$triple["o_lang"] = $o_lang;

					$arcTriples[] = $triple;
				}
			}
		}

		if( $bnodes )
		{
			foreach( array_unique( $bnodes_to_add ) as $bnode )
			{
				$arcTriples = array_merge( $arcTriples, $this->g->resource( $bnode )->toArcTriples() );
			}
		}
		return $arcTriples;
	}

	public function serialize( $type = "RDFXML" )
	{
		$serializer = ARC2::getSer( $type, array( "ns" => $this->g->ns ) );
		return $serializer->getSerializedTriples( $this->toArcTriples() );
	}

	public function load()
	{
		return $this->g->load( $this->uri );
	}

	public function loadSameAsOrg( $prefix )
	{
		$sameasorg_uri = "http://sameas.org/rdf?uri=".urlencode( $this->uri );
		$n = $this->g->load( $sameasorg_uri );
		$n+= $this->loadSameAs( $prefix );
		return $n;
	}

	function loadDataGovUKBackLinks()
	{
		$backurl = "http://backlinks.psi.enakting.org/resource/rdf/".$this->uri;
		return $this->g->load( $backurl, array(), array( $this->uri=>1 ) );
	}

	public function loadSameAs( $prefix=null )
	{
		$cnt = 0;
		foreach( $this->all( "owl:sameAs" ) as $sameas )
		{
			$this->g->forceString( $sameas );
			if( $prefix && substr( $sameas, 0, strlen($prefix )) != $prefix )
			{
				continue;
			}

			$cnt += $this->g->load( $sameas, array( $sameas=>$this->uri ) );
		}
		return $cnt;
	}

	public function type()
	{
		return $this->get( "rdf:type" );
	}

	public function types()
	{
		return $this->all( "rdf:type" );
	}

	public function isType( /* List */ )
	{
		$args = func_get_args();
		if (empty($args)) {
			return false;
		}

		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		foreach( $this->allString( 'rdf:type' ) as $type )
		{

			foreach( $args as $arg )
			{
				$uri = $this->g->expandURI( $arg );
				if( $uri == $type ) { return true; }
			}
		}

		return false;
	}

	public function hasLabel()
	{
		return $this->has( $this->g->labelRelations() );
	}
	public function label()
	{
		return $this->getLiteral( $this->g->labelRelations() );
	}

	public function link()
	{
		return "<a title='".$this->uri."' href='".$this->uri."'>".$this->uri."</a>";
	}
	public function prettyLink()
	{
		if( substr( $this->uri, 0, 4 ) == "tel:" )
		{
			$label = substr( $this->uri, 4 );
			if( $this->hasLabel() ) { $label = $this->label(); }
			$icon = "";
			$iconURL = $this->g->telIcon();
			if( $iconURL != "" );
			{
				$icon =
"<a title='".$this->uri."' href='".$this->uri."'><img style='padding-right:0.2em;' src='$iconURL' /></a>";
			}
			return
"<span style='white-space:nowrap'>$icon<a title='".$this->uri."' href='".$this->uri."'>$label</a></span>";

			# icon adapted from cc-by icon at http://pc.de/icons/
		}

		if( substr( $this->uri, 0, 7 ) == "mailto:" )
		{
			$label = substr( $this->uri, 7 );
			if( $this->hasLabel() ) { $label = $this->label(); }
			$icon = "";
			$iconURL = $this->g->mailtoIcon();
			if( $iconURL != "" );
			{
				$icon =
"<a title='".$this->uri."' href='".$this->uri."'><img style='padding-right:0.2em;' src='$iconURL' /></a>";
			}
			return
"<span style='white-space:nowrap'>$icon<a title='".$this->uri."' href='".$this->uri."'>$label</a></span>";
			# icon adapted from cc-by icon at http://pc.de/icons/
		}

		$label = $this->uri;
		if( $this->hasLabel() ) { $label = $this->label(); }
		return "<a title='".$this->uri."' href='".$this->uri."'>$label</a>";
	}

	public function dumpText()
	{
		$r = "";
		$plist = array();
		foreach( $this->relations() as $prop )
		{
			$olist = array();
			foreach( $this->all( $prop ) as $obj )
			{
				$olist []= $obj->dumpValueText();
			}
			$arr = "->";
			if( is_a( $prop, "Graphite_InverseRelation" ) ) { $arr = "<-"; }
			$plist []= "$arr ".$this->g->shrinkURI($prop)." $arr ".join( ", ",$olist );
		}
		return $this->g->shrinkURI($this->uri)."\n    ".join( ";\n    ", $plist )." .\n";
	}

	public function dump( $options = array() )
	{
		$r = "";
		$plist = array();
		foreach( $this->relations() as $prop )
		{
			$olist = array();
			$all = $this->all( $prop );
			foreach( $all as $obj )
			{
				$olist []= $obj->dumpValue($options);
			}
			if( is_a( $prop, "Graphite_InverseRelation" ) )
			{
				$pattern = "<span style='font-size:130%%'>&larr;</span> is <a title='%s' href='%s' style='text-decoration:none;color: green'>%s</a> of <span style='font-size:130%%'>&larr;</span> %s";
			}
			else
			{
				$pattern = "<span style='font-size:130%%'>&rarr;</span> <a title='%s' href='%s' style='text-decoration:none;color: green'>%s</a> <span style='font-size:130%%'>&rarr;</span> %s";
			}
			$this->g->forceString( $prop );
			$plist []= sprintf( $pattern, $prop, $prop, $this->g->shrinkURI($prop), join( ", ",$olist ));
		}
		$r.= "\n<a name='".htmlentities($this->uri)."'></a><div style='text-align:left;font-family: arial;padding:0.5em; background-color:lightgrey;border:dashed 1px grey;margin-bottom:2px;'>\n";
		if( isset($options["label"] ) )
		{
			$label = $this->label();
			if( $label == "[NULL]" ) { $label = ""; } else { $label = "<strong>$label</strong>"; }
			if( $this->has( "rdf:type" ) )
			{
				if( $this->get( "rdf:type" )->hasLabel() )
				{
					$typename = $this->get( "rdf:type" )->label();
				}
				else
				{
					$bits = preg_split( "/[\/#]/", @$this->get( "rdf:type" )->uri );
					$typename = array_pop( $bits );
					$typename = preg_replace( "/([a-z])([A-Z])/","$1 $2",$typename );
				}
				$r .= preg_replace( "/>a ([AEIOU])/i", ">an $1", "<div style='float:right'>a $typename</div>" );
			}
			if( $label != "" ) { $r.="<div>$label</div>"; }
		}
		$r.= " <!-- DUMP:".$this->uri." -->\n <div><a title='".$this->uri."' href='".$this->uri."' style='text-decoration:none'>".$this->g->shrinkURI($this->uri)."</a></div>\n";
		$r.="  <div style='padding-left: 3em'>\n  <div>".join( "</div>\n  <div>", $plist )."</div></div><div style='clear:both;height:1px; overflow:hidden'>&nbsp;</div></div>";
		return $r;
	}

	function __toString() {
		return !empty($this->uri) ? (string)$this->uri : "";
	}
	function dumpValue($options=array())
	{
		$label = $this->dumpValueText();
		if( $this->hasLabel() && @$options["labeluris"] )
		{
			$label = $this->label();
		}
		$href = $this->uri;
		if( @$options["internallinks"] )
		{
			$href = "#".htmlentities($this->uri);
		}
		return "<a href='".$href."' title='".$this->uri."' style='text-decoration:none;color:red'>".$label."</a>";
	}
	function dumpValueText() { return $this->g->shrinkURI( $this->uri ); }
	function nodeType() { return "#resource"; }

	function prepareDescription()
	{
		return new Graphite_Description( $this );
	}

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
