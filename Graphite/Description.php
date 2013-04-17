<?php
# A Graphite Description is an object to describe the routes of attributes
# which we wish to use to describe a specific resource, and to allow that
# to be nicely expressed in JSON.

class Graphite_Description
{
	var $graph;
	var $resource;
	var $routes = array();
	var $tree = array(
		"+" => array(),
		"-" => array() );
	# header, footer

	function __construct( $resource )
	{
		$this->graph = $resource->g;
		$this->resource = $resource;
	}

	function addRoute( $route )
	{
		$this->routes[$route] = true;
		$preds = preg_split( '/\//', $route );
		$treeptr = &$this->tree;
		foreach( $preds as $pred )
		{
			$dir = "+";
			if( substr($pred,0,1) == "-" ) { $pred = substr($pred,1); $dir = "-"; }
			if( !isset( $treeptr[$dir][$pred] ) )
			{
				$treeptr[$dir][$pred] = array( "+" => array(), "-" => array() );
			}
			$treeptr = &$treeptr[$dir][$pred];
		}
	}

	function toDebug()
	{
		$json = array();
		$this->_jsonify( $this->tree, $this->resource, $json );

		return print_r( $json, 1 );
	}

	function toJSON()
	{
		$json = array();
		$this->_jsonify( $this->tree, $this->resource, $json );

		return json_encode( $json );
	}

	function _jsonify( $tree, $resource, &$json )
	{
		foreach( $resource->relations() as $relation )
		{
			$code = $this->graph->shrinkURI( $relation );
			$jsonkey = $code;
			$dir = "+";
			if( $relation->nodeType() == "#inverseRelation" )
			{
				$dir = "-";
				$jsonkey = "$jsonkey of";
			}
			if( !isset($tree[$dir]["*"]) && !isset($tree[$dir][$code]) ) { continue; }

			foreach( $resource->all( $relation ) as $value )
			{
				if( is_a( $value, "Graphite_Literal" ) )
				{
					$json[$jsonkey][] = Graphite::asString($value);
				}
				else
				{
					$subjson = array();
					$uri = Graphite::asString($value);
					if( substr( $uri,0,2 ) != "_:" ) { $subjson["_uri"] = $uri; }
					if( isset( $tree[$dir][$code]) )
					{
						$this->_jsonify( $tree[$dir][$code], $value, $subjson );
					}
					if( isset( $tree[$dir]["*"]) )
					{
						$this->_jsonify( $tree[$dir]["*"], $value, $subjson );
					}
					$json[$jsonkey][] = $subjson;
				}
			}
		}
	}

	function toGraph()
	{
		$new_graph = new Graphite();
		$this->_tograph( $this->tree, $this->resource, $new_graph );
		return $new_graph;
	}

	function _tograph( $tree, $resource, &$new_graph )
	{
		foreach( $resource->relations() as $relation )
		{
			$code = $this->graph->shrinkURI( $relation );
			$dir = "+";
			if( $relation->nodeType() == "#inverseRelation" )
			{
				$dir = "-";
			}

			if( !isset($tree[$dir]["*"]) && !isset($tree[$dir][$code]) ) { continue; }

			foreach( $resource->all( $relation ) as $value )
			{
				if( is_a( $value, "Graphite_Literal" ) )
				{
					$datatype = $value->datatype();
					if( !isset($datatype) ) { $datatype='literal'; }
					$new_graph->addTriple(
						Graphite::asString($resource),
						Graphite::asString($relation),
						Graphite::asString($value),
						$datatype,
						$value->language() );
				}
				else
				{
					if( isset( $tree[$dir][$code]) )
					{
						$this->_tograph( $tree[$dir][$code], $value, $new_graph );
					}
					if( isset( $tree[$dir]["*"]) )
					{
						$this->_tograph( $tree[$dir]["*"], $value, $new_graph );
					}
					if( $dir == "+" )
					{
						$new_graph->addTriple(
							Graphite::asString($resource),
							Graphite::asString($relation),
							Graphite::asString($value) );
					}
					else
					{
						$new_graph->addTriple(
							Graphite::asString($value),
							Graphite::asString($relation),
							Graphite::asString($resource) );
					}
				}
			}
		}
	}

	function loadSPARQL( $endpoint, $debug = false )
	{
		$bits = $this->_toSPARQL( $this->tree, "", null, "" );
		$n = 0;
		foreach( $bits as $bit )
		{
			$sparql = "CONSTRUCT { ".$bit['construct']." } WHERE { ".$bit['where']." }";
			if( $debug || @$_GET["_graphite_debug"] ) {
				 print "<div style='padding: 1em'><tt>\n\n".htmlspecialchars($sparql)."</tt></div>\n\n";
			}
			$n+=$this->graph->loadSPARQL( $endpoint, $sparql );
		}
		return $n;
	}

	function _toSPARQL($tree, $suffix, $in_dangler = null, $sparqlprefix = "" )
	{
		$bits = array();
		if( !isset( $in_dangler ) )
		{
			$in_dangler = "<".Graphite::asString($this->resource).">";
		}

		$i = 0;
		foreach( $tree as $dir=>$routes )
		{
			if( sizeof($routes) == 0 ) { continue; }

			$pres = array();
			if( isset($routes["*"]) )
			{
				$sub = "?s".$suffix."_".$i;
				$pre = "?p".$suffix."_".$i;
				$obj = "?o".$suffix."_".$i;

				if( $dir == "+" )
				{
					$out_dangler = $obj;
					$sub = $in_dangler;
				}
				else # inverse
				{
					$out_dangler = $sub;
					$obj = $in_dangler;
				}

				$construct = "$sub $pre $obj . ";
				$where = "$sparqlprefix $sub $pre $obj .";
				if( isset( $routes["*"] ) )
				{
					$bits_from_routes = $this->_toSPARQL( $routes["*"], $suffix."_".$i, $out_dangler, "" );
					$i++;
					foreach( $bits_from_routes as $bit )
					{
						$construct .= $bit["construct"];
						$where .= " OPTIONAL { ".$bit["where"]." }";
					}
				}
				$bits []= array( "where"=>$where, "construct"=>$construct );

				foreach( $routes as $pred=>$route )
				{
					if( $pred == "*" ) { continue; }

					$pre = "<".$this->graph->expandURI( $pred ).">";

					$bits_from_routes = $this->_toSPARQL( $route, $suffix."_".$i, $out_dangler, "$sparqlprefix $sub $pre $obj ." );
					$i++;
					foreach( $bits_from_routes as $bit )
					{
						$bits []= $bit;
					}
				}
			}
			else
			{
				foreach( array_keys( $routes ) as $pred )
				{
					$sub = "?s".$suffix."_".$i;
					$pre = "<".$this->graph->expandURI( $pred ).">";
					$obj = "?o".$suffix."_".$i;

					if( $dir == "+" )
					{
						$out_dangler = $obj;
						$sub = $in_dangler;
					}
					else # inverse
					{
						$out_dangler = $sub;
						$obj = $in_dangler;
					}

					$bits_from_routes = $this->_toSPARQL( $routes[$pred],$suffix."_".$i, $out_dangler, "" );
					$i++;

					$construct = "$sub $pre $obj . ";
					$where = "$sparqlprefix $sub $pre $obj .";
					foreach( $bits_from_routes as $bit )
					{
						$construct .= $bit["construct"];
						$where .= " OPTIONAL { ".$bit["where"]." }";
					}

					$bits []= array( "where"=>$where, "construct"=>$construct );
				}
			}
		}

		return $bits;
	} # end _toSPARQL

	function getFormats()
	{
		return array(
			"json"=>"JSON",
			"nt"=>"RDF (Triples)",
			"ttl"=>"RDF (Turtle)",
			"rdf"=>"RDF (XML)",
			"rdf.html" => "RDF (RDF HTML Debug)",
			"kml" => "KML",
			"ics" => "iCalendar",
		);
	}

	function handleFormat( $format )
	{
		if( $format == 'json' )
		{
			if( isset( $_GET['callback'] ) )
			{
				header( "Content-type: application/javascript" );
				print $_GET['callback']."( ".$this->toJSON()." );\n";
			}
			else
			{
				header( "Content-type: application/json" );
				print $this->toJSON();
			}

			return true;
		}

		if( $format == 'ttl' )
		{
			header( "Content-type: text/turtle" );
			print $this->toGraph()->serialize( "Turtle" );
			return true;
		}

		if( $format == 'nt' )
		{
			header( "Content-type: text/plain" );
			print $this->toGraph()->serialize( "NTriples" );
			return true;
		}

		if( $format == 'rdf' )
		{
			header( "Content-type: application/rdf+xml" );
			print $this->toGraph()->serialize( "RDFXML" );
			return true;
		}

		if( $format == 'kml' )
		{
			header( "Content-type: application/vnd.google-earth.kml+xml" );
			print $this->toGraph()->toKml();
			return true;
		}

		if( $format == 'ics' )
		{
			header( "Content-type: text/calendar" );
			print $this->toGraph()->toIcs();
			return true;
		}

		if( $format == 'rdf.html' )
		{
			header( "Content-type: text/html" );
			print $this->toGraph()->dump();
			return true;
		}

		if( $format == 'debug' )
		{
			header( "Content-type: text/plain" );
			print $this->toDebug();
			return true;
		}

		return false;
	}
}
