<?php
# (c)2010,2011,2012 Christopher Gutteridge / University of Southampton
# some extra features and bugfixes by Bart Nagel
# License: LGPL
# Version 1.5

# Requires ARC2 to be included.
# suggested call method:
#   include_once("arc/ARC2.php");
#   include_once("Graphite.php");

# Similar libraries
#  EasyRDF - http://code.google.com/p/easyrdf/
#  SimpleGraph - http://code.google.com/p/moriarty/wiki/SimpleGraph
#
# I've used function calls in common with EasyRDF, where it makes sense
# to do so. Easy RDF now uses our dump() style. We're one big happy linked
# data community!

# todo:
# hasRelationValue, hasRelation, filter

# Load ARC2 assuming it's not already been loaded. Requires ARC2.php to be
# in the path.
if( !class_exists( "ARC2" ) )
{
	require_once 'ARC2.php';
}

require_once 'Graphite/Retriever.php';

class Graphite
{

	/**
	 * @var Graphite_Retriever $retriever
	 */
	protected $retriever;

	/**
	 * Create a new instance of Graphite. @see ns() for how to specify a namespace map and a list of pre-declared namespaces.
	 */
	public function __construct( $namespaces = array(), $uri = null )
	{
		$this->workAround4StoreBNodeBug = false;
		$this->t = array( "sp" => array(), "op"=>array() );
		foreach( $namespaces as $short=>$long )
		{
			$this->ns( $short, $long );
		}
		$this->ns( "foaf", "http://xmlns.com/foaf/0.1/" );
		$this->ns( "dc",   "http://purl.org/dc/elements/1.1/" );
		$this->ns( "dcterms",  "http://purl.org/dc/terms/" );
		$this->ns( "dct",  "http://purl.org/dc/terms/" );
		$this->ns( "rdf",  "http://www.w3.org/1999/02/22-rdf-syntax-ns#" );
		$this->ns( "rdfs", "http://www.w3.org/2000/01/rdf-schema#" );
		$this->ns( "owl",  "http://www.w3.org/2002/07/owl#" );
		$this->ns( "xsd",  "http://www.w3.org/2001/XMLSchema#" );
		$this->ns( "cc",   "http://creativecommons.org/ns#" );
		$this->ns( "bibo", "http://purl.org/ontology/bibo/" );
		$this->ns( "skos", "http://www.w3.org/2004/02/skos/core#" );
		$this->ns( "geo",  "http://www.w3.org/2003/01/geo/wgs84_pos#" );
		$this->ns( "sioc", "http://rdfs.org/sioc/ns#" );
		$this->ns( "oo",   "http://purl.org/openorg/" );
		$this->ns( "prov", "http://www.w3.org/ns/prov#" );

		$this->loaded = array();
		$this->debug = false;
		$this->arc2config = null;

		$this->lang = "en";

		$this->labelRelations = array(
			"skos:prefLabel", "rdfs:label", "foaf:name", "dct:title", "dc:title", "sioc:name" );
		$this->mailtoIcon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAALCAIAAAAvJUALAAAABGdBTUEAALGPC/xhBQAAAAlwSFlz
AAALEwAACxMBAJqcGAAAAAd0SU1FB9wCAhEsArM6LtoAAAF/SURBVBjTfZFNTxNhFIXf985QypTA
OLQdihMrHya6FFFqJBp+Gz+ABTFx45+w0QSCO5WFCisMUYMLhBZahdjp13vuveOCxGiiPnkWZ3FW
59iD7bXK/KLhnrFk/kWm1g9aRx8oTpL93c2gPFcIqRDavxqUqx/3X0XlEgkPl1eW3tQ32CYZDzJ0
/pD7Ssnbrae3794SHhAzw7na6sPXz9YHpqomLwxhFoZmOXjzOy8e36ktwzlmJgYUTobD2qOVd5tP
fqRjxispnPGKab+wU99Yun9PMFQ4BogBYQgjE7k2W/28Vz8+avrRg8bXxqfd5ws3b/S7vcsCAz7D
CSyRd3baLsXFyYnxL4d7B+9fxtNTcwvX8/nRxslpZSZWFYbzASh73y/OJybH2Tmy5mpSIUvTlbJP
lp2bisLmcbNYugLAF6CX8ghZq6IqxpicR0kSe0TKuJw7N+J1Ox2B8QGXphxFoQj/foiI/spBMNo6
a0PH/JNGOyzot9a5+S+Z6kW38xPpxe30BrwPeQAAAABJRU5ErkJggg==
';
		$this->telIcon = 'data:image/png;base64,
iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAAABGdBTUEAALGPC/xhBQAAAAZiS0dE
AP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9wCBxIsM9d8YIsAAAItSURB
VCjPTZLLS5VhEMZ/7/ediyiek1csOSc9ZQqhQpAVWC3auGyh9BfUKjFw4aZVQW0SxD+hVeU+WklW
RkVQoEYoZXhMyeupMPV878y0+MSaxczDwMzzzMUBDA6NDPR0N4ymkmFOTTGD2MVmB1hEi+9nS8Pj
Y/cn3ODQyMD4nQuP9//su6nXc+Sb62k93ggYGGB6WAhGOuXs1r25a+HNG1efnS5UZx8+es7C11WW
ims0N9WQqUpjKmAKGGYCavjIu+1f/nyQSrrc1PQs6+slfBRR+rnD1lYJU8FUUBFU/GEUFcKQXMLU
SIaxDNNY1u7uPqoRKnYg2TAslmyGmRKoKifyDQQuTtRmK2nN16FeUBVMfcwuMUYFDBJmSqY6TeDA
VKmqTFKVTqDiwQw9ZPy3KDMliOmFky31gLL0fYN3H76gKqh6THw8wn9zYkaAGSaeS+faqM1UYqK8
ejvPx5lFnAm7e7ssLK5QLu9jEsXNMBKYoSokAug9W+Dp5CzlyPPizTzLq5tsl/6wtvGb5qYsne1N
tBUaQY3AMEw9IhFtrfVc7ClQkQrZ2yvz6fMKqz+2URWWljeZnJ7HIg8YgXgtIh4TISqX6Wpvou9y
B3U1FaSTAQGgqjgHNZkKDMGLFcOWjt7lK2cS/RKpw+ITZKtTdJ06Sm1NJZulHXwk5I8dob+vk1TC
2ZOXdt0BDA3fHuguhKNBQA4Md/CmzjnCwIGLH70cWXHmmw2PPbg78Rex1nK3Gk8UNQAAAABJRU5E
rkJggg==
';

		$this->firstGraphURI = null;
		if( $uri )
		{
			$this->load( Graphite::asString($uri) );
		}

		$this->bnodeprefix = 0;
		$this->setRetriever(new Graphite_Retriever($this));
	}

	public function setRetriever(Graphite_Retriever $retriever) {
		$this->retriever = $retriever;
	}

	/**
	 * Graphite uses ARC2 to parse RDF, which isn't as fast as using a compiled library. I may add support for <a href='http://www4.wiwiss.fu-berlin.de/bizer/rdfapi/'>RAP</a> or something similar. When Graphite loads a triple it indexes it by both subject &amp; object, which also takes a little time. To address this issue, freeze and thaw go some way to help speed things up. freeze takes a graph object, including all the namespaces set with ns() and saves it to disk as a serialised PHP object, which is much faster to load then a large RDF file. It's ideal in a situation where you want to build a site from a single RDF document which is updated occasionally. <a href='https://github.com/cgutteridge/Graphite/blob/master/examples/freeze.php'>This example</a> is a command line script you can modify to load and freeze a graph.
	 */
	public function freeze( $filename )
	{
		$fh = fopen($filename, 'w') or die("can't open file");
		fwrite($fh, serialize( $this ) );
		fclose($fh);
	}

	/**
	 * Graphite uses ARC2 to parse RDF, which isn't as fast as using a compiled library. I may add support for <a href='http://www4.wiwiss.fu-berlin.de/bizer/rdfapi/'>RAP</a> or something similar. When Graphite loads a triple it indexes it by both subject &amp; object, which also takes a little time. To address this issue, freeze and thaw go some way to help speed things up. freeze takes a graph object, including all the namespaces set with ns() and saves it to disk as a serialised PHP object, which is much faster to load then a large RDF file. It's ideal in a situation where you want to build a site from a single RDF document which is updated occasionally. <a href='https://github.com/cgutteridge/Graphite/blob/master/examples/freeze.php'>This example</a> is a command line script you can modify to load and freeze a graph.
	 */
	public static function thaw( $filename )
	{
		return unserialize( join( "", file( $filename )));
	}

	public static function __set_state($data) // As of PHP 5.1.0
	{
		$graph = new Graphite;
		$graph->bnodeprefix = $data['bnodeprefix'];
		$graph->firstGraphURI = $data['firstGraphURI'];
		$graph->loaded = $data['loaded'];
		$graph->ns = $data['ns'];
		$graph->workAround4StoreBNodeBug = $data["workAround4StoreBNodeBug"];
		$graph->t = $data["t"];
		return $graph;
	}

	/**
	 * $dir should be a directory the webserver has permission to read and write to. Any RDF/XML documents which graphite downloads will be saved here. If a cache exists and is newer than $age seconds then load() will use the document in the cache directory in preference to doing an HTTP request. $age defaults to 24*60*60 - 24 hours. This including this function can seriously improve graphite performance! If you want to always load certain documents, load them before setting the cache.
	 *
	 * @todo Shift to Graphite_Retriever
	 */
	public function cacheDir( $dir, $age = 86400 ) # default age is 24 hours
	{
		$error = "";
		if( !file_exists( $dir ) ) { $error = "No such directory: $dir"; }
		elseif( !is_dir( $dir ) ) { $error = "Not a directory: $dir"; }
		elseif( !is_writable( $dir ) ) { $error = "Not writable: $dir"; }
		if( $error ) {
			print "<ul><li>Graphite cacheDir error: $error</li></ul>";
		}
		else
		{
			$this->cacheDir = $dir;
			$this->cacheAge = $age;
		}
	}

	public function setARC2Config( $config ) { $this->arc2config = $config; }
	public function setDebug( $boolean ) { $this->debug = $boolean; }
	public function setLang( $lang ) { $this->lang = $lang; }

	/**
	 * Return a list of the relations currently used for $resource->label(), if called with a parameter then this should be an array to <strong>replace</strong> the current list. To just add additonal relation types to use as labels, use addLabelRelation($relation).
	 */
	public function labelRelations( $new = null )
	{
		$lr = $this->labelRelations;
		if( isset( $new ) ) { $this->labelRelations = $new; }
		return $lr;
	}

	/**
	 * Return a list of the relations currently used for $resource->label(), if called with a parameter then this should be an array to <strong>replace</strong> the current list. To just add additonal relation types to use as labels, use addLabelRelation($relation).
	 */
	public function addLabelRelation( $addition )
	{
		$this->labelRelations []= $addition;
	}

	/**
	 * Get or set the URL of the icon used for mailto: and tel: links in prettyLink(). If set to an empty string then no icon will be shown.
	 */
	public function mailtoIcon( $new = null )
	{
		$icon = $this->mailtoIcon;
		if( isset( $new ) ) { $this->mailtoIcon = $new; }
		return $icon;
	}

	/**
	 * Get or set the URL of the icon used for mailto: and tel: links in prettyLink(). If set to an empty string then no icon will be shown.
	 */
	public function telIcon( $new = null )
	{
		$icon = $this->telIcon;
		if( isset( $new ) ) { $this->telIcon = $new; }
		return $icon;
	}

	function removeFragment( $uri )
	{
		return preg_replace( "/#.*/", "", $uri );
	}

	function loaded( $uri )
	{
		if( !array_key_exists( $this->removeFragment( $uri ), $this->loaded ) )
		{
			return false;
		}
		return $this->loaded[$this->removeFragment( $uri )];
	}

	/**
	 * Load the RDF from the given URI or URL. Return the number of triples loaded.
	 */
	public function load( $uri, $aliases = array(), $map = array() )
	{
		$uri = $this->expandURI( Graphite::asString($uri) );

		if( substr( $uri,0,5 ) == "data:" )
		{
			$data = urldecode( preg_replace( "/^data:[^,]*,/","", $uri ) );
			$parser = ARC2::getTurtleParser( $this->arc2config );
			$parser->parse( $uri, $data );
		}
		else
		{
			if( $this->loaded( $uri ) !== false ) { return $this->loaded( $uri ); }

			$data = $this->retriever->retrieve($uri);

			if(!empty($data))
			{
				$parser = ARC2::getRDFXMLParser( $this->arc2config );
				$parser->parse( $uri, $data );
			}
			else
			{
				$opts = array();
 				if( isset($this->arc2config) ) { $opts =  $this->arc2config; }
				$opts['http_accept_header']= 'Accept: application/rdf+xml; q=0.9, text/turtle; q=0.8, */*; q=0.1';

				$parser = ARC2::getRDFParser($opts);
				# Don't try to load the same URI twice!

				if( !isset( $this->firstGraphURI ) )
				{
					$this->firstGraphURI = $uri;
				}
				$parser->parse( $uri );
			}
		}

		$errors = $parser->getErrors();
		$parser->resetErrors();
		if( sizeof($errors) )
		{
			if( $this->debug )
			{
				print "<h3>Error loading: $uri</h3>";
				print "<ul><li>".join( "</li><li>",$errors)."</li></ul>";
			}
			return 0;
		}
		$this->loaded[$this->removeFragment( $uri )] = $this->addTriples( $parser->getTriples(), $aliases, $map );
		return $this->loaded( $uri );
	}

	/**
	 * This uses one or more SPARQL queries to the given endpoint to get all the triples required for the description. The return value is the total number of triples added to the graph.
	 */
	function loadSPARQL( $endpoint, $query, $opts=array() )
	{
		$url = $endpoint."?query=".urlencode($query);
		foreach( $opts as $k=>$v ) { $url .= "&$k=".urlencode($v); }
		return $this->load( $url );
	}

	/**
	 * Take a base URI and a string of turtle RDF and load the new triples into the graph. Return the number of triples loaded.
	 */
	function addTurtle( $base, $data )
	{
		$parser = ARC2::getTurtleParser( $this->arc2config );
		$parser->parse( $base, $data );
		$errors = $parser->getErrors();
		$parser->resetErrors();
		if( sizeof($errors) )
		{
			if( $this->debug )
			{
				print "<h3>Error loading turtle string</h3>";
				print "<ul><li>".join( "</li><li>",$errors)."</li></ul>";
			}
			return 0;
		}
		return $this->addTriples( $parser->getTriples() );
	}

	/**
	 * As for addTurtle but load a string of RDF XML
	 *
	 * @see addTurtle
	 */
	function addRDFXML( $base, $data )
	{
		$parser = ARC2::getRDFXMLParser( $this->arc2config );
		$parser->parse( $base, $data );
		$errors = $parser->getErrors();
		$parser->resetErrors();
		if( sizeof($errors) )
		{
			if( $this->debug )
			{
				print "<h3>Error loading RDFXML string</h3>";
				print "<ul><li>".join( "</li><li>",$errors)."</li></ul>";
			}
			return 0;
		}
		return $this->addTriples( $parser->getTriples() );
	}

	/**
	 * Replace bnodes shorthand with configured bnodeprefix in URI
	 *
	 * @param string $uri
	 */
	function addBnodePrefix( $uri )
	{
		return preg_replace( "/^_:/", "_:g" . $this->bnodeprefix . "-", $uri );
	}

	/**
	 * Add triples to the graph from an ARC2 datastrcture. This is the inverse of toArcTriples.
	 *
	 * @see ARC2
	 * @see toArcTriples
	 */
	function addTriples( $triples, $aliases = array(), $map = array() )
	{
		$this->bnodeprefix++;

		foreach( $triples as $t )
		{
			if( $this->workAround4StoreBNodeBug )
			{
				if( $t["s"] == "_:NULL" || $t["o"] == "_:NULL" ) { continue; }
			}
			$t["s"] = $this->addBnodePrefix( $this->cleanURI($t["s"]) );
			if( !isset($map[$t["s"]]) ) { continue; }
			$t["p"] = $this->cleanURI($t["p"]);

			# work around for bug in ARC2 turtle parser. 
			if( $t["o_type"] == "bnode" ) { $t["o_datatype"] = ""; } 

			if( $t["p"] != "http://www.w3.org/2002/07/owl#sameAs" ) { continue; }
			$aliases[$this->addBnodePrefix( $t["o"] )] = $t["s"];
		}
		foreach( $triples as $t )
		{
			$datatype = @$t["o_datatype"];
			if( @$t["o_type"] == "literal" && !$datatype ) { $datatype = "literal"; }
			$this->addTriple( $t["s"], $t["p"], $t["o"], $datatype, @$t["o_lang"], $aliases );
		}
		return sizeof( $triples );
	}

	/**
	 * Add a single triple directly to the graph. Only addCompressedTriple accepts shortended URIs, eg foaf:name.
	 *
	 * @see addTriple
	 */
	function addCompressedTriple( $s,$p,$o, $o_datatype=null,$o_lang=null,$aliases=array() )
	{
		$this->t( $s,$p,$o, $o_datatype,$o_lang,$aliases );
	}

	/**
	 * Alias for addCompressedTriple for more readable code.
	 *
	 * @see addTriple
	 */
	function t( $s,$p,$o, $o_datatype=null,$o_lang=null,$aliases=array() )
	{
		$s = $this->expandURI( $s );
		$p = $this->expandURI( $p );
		if( !isset( $o_datatype ) )
		{
			# only expand $o if it's a non literal triple
			$o = $this->expandURI( $o );
		}
		if( isset( $o_datatype ) && $o_datatype != "literal" )
		{
			$o_datatype = $this->expandURI( $o_datatype );
		}
		$this->addTriple( $s,$p,$o, $o_datatype,$o_lang,$aliases );
	}

	/**
	 * Add a single triple directly to the graph.
	 *
	 * @see addCompressedTriple
	 */
	function addTriple( $s,$p,$o,$o_datatype=null,$o_lang=null,$aliases=array() )
	{
		if( $this->workAround4StoreBNodeBug )
		{
			if( $s == "_:NULL" || $o == "_:NULL" ) { return; }
		}
		$s = $this->addBnodePrefix( $this->cleanURI( $s ) );
		if( !isset($o_datatype) || $o_datatype == "" )
		{
			$o = $this->addBnodePrefix( $this->cleanURI( $o ) );
		}

		if( isset($aliases[$s]) ) { $s=$aliases[$s]; }
		if( isset($aliases[$p]) ) { $p=$aliases[$p]; }
		if( isset($aliases[$o]) ) { $o=$aliases[$o]; }

		if( isset( $o_datatype ) && $o_datatype )
		{
			if( $o_datatype == 'literal' ) { $o_datatype = null; }
			# check for duplicates

			# if there's existing triples with this subject & predicate,
			# check for duplicates before adding this triple.
			if( array_key_exists( $s, $this->t["sp"] ) 
			 && array_key_exists( $p, $this->t["sp"][$s] ) )
			{
				foreach( $this->t["sp"][$s][$p] as $item )
				{
					# no need to add triple if we've already got it.
					if( 
					 is_array( $item )
					 && $item["v"] === $o 
				 	 && $item["d"] === $o_datatype 
				 	 && $item["l"] === $o_lang ) { return; }
				}
			}
			$this->t["sp"][$s][$p][] = array(
				"v"=>$o,
				"d"=>$o_datatype,
				"l"=>$o_lang );
		}
		else
		{
			# if there's existing triples with this subject & predicate,
			# check for duplicates before adding this triple.
			if( array_key_exists( $s, $this->t["sp"] ) 
			 && array_key_exists( $p, $this->t["sp"][$s] ) )
			{
				foreach( $this->t["sp"][$s][$p] as $item )
				{
					# no need to add triple if we've already got it.
					if( $item === $o ) { return; } 
				}
			}
			$this->t["sp"][$s][$p][] = $o;
			$this->t["op"][$o][$p][] = $s;
		}
	}

	/**
	 * Returns all triples of which this resource is the subject in Arc2's internal triples format.
	 */
	public function toArcTriples()
	{
		$arcTriples = array();
		foreach( $this->allSubjects() as $s )
		{
			foreach( $s->toArcTriples( false ) as $t )
			{
				$arcTriples []= $t;
			}
		}
		return $arcTriples;
	}

	/**
	 * Returns a serialization of every temporal entity as an iCalendar file
	 */
	public function toIcs()
	{
		$r = "";

		$r .= "BEGIN:VCALENDAR\r\n";
		$r .= "PRODID:-//Graphite//EN\r\n";
		$r .= "VERSION:2.0\r\n";
		$r .= "CALSCALE:GREGORIAN\r\n";
		$r .= "METHOD:PUBLISH\r\n";

		$done = array();

		foreach($this->allSubjects() as $res)
		{
			if($res->has("http://purl.org/NET/c4dm/event.owl#time"))
			{
				$time = $res->get("http://purl.org/NET/c4dm/event.owl#time");
			} else {
				$time = $res;
			}
			$timeuri = "" . $time;
			if(array_key_exists($timeuri, $done))
			{
				continue;
			}
			$done[$timeuri] = $timeuri;
			$starttime = 0;
			$endtime = 0;
			if($time->has("http://purl.org/NET/c4dm/timeline.owl#start"))
			{
				$starttime = strtotime($time->get("http://purl.org/NET/c4dm/timeline.owl#start"));
			}
			if($time->has("http://purl.org/NET/c4dm/timeline.owl#beginsAtDateTime"))
			{
				$starttime = strtotime($time->get("http://purl.org/NET/c4dm/timeline.owl#beginsAtDateTime"));
			}
			if($time->has("http://purl.org/NET/c4dm/timeline.owl#end"))
			{
				$endtime = strtotime($time->get("http://purl.org/NET/c4dm/timeline.owl#end"));
			}
			if($time->has("http://purl.org/NET/c4dm/timeline.owl#endsAtDateTime"))
			{
				$endtime = strtotime($time->get("http://purl.org/NET/c4dm/timeline.owl#endsAtDateTime"));
			}
			if(($starttime == 0) | ($endtime == 0))
			{
				continue;
			}
			$location = "";
			if($res->has("http://purl.org/NET/c4dm/event.owl#place"))
			{
				$location = $res->all("http://purl.org/NET/c4dm/event.owl#place")->label()->join(", ");
			}
			$title = str_replace("\n", "\\n", $res->label());
			$description = "";
			if($res->has("http://purl.org/dc/terms/description"))
			{
				$description = str_replace("\n", "\\n", $res->get("http://purl.org/dc/terms/description"));
			}

			$r .= "BEGIN:VEVENT\r\n";
			$r .= "DTSTART:" . gmdate("Ymd", $starttime) . "T" . gmdate("His", $starttime) . "Z\r\n";
			$r .= "DTEND:" . gmdate("Ymd", $endtime) . "T" . gmdate("His", $endtime) . "Z\r\n";
			$r .= "UID:" . $res . "\r\n";
			$r .= "SUMMARY:" . $title . "\r\n";
			$r .= "DESCRIPTION:" . $description . "\r\n";
			$r .= "LOCATION:" . $location . "\r\n";
			$r .= "END:VEVENT\r\n";
		}

		$r .= "END:VCALENDAR\r\n";

		return($r);
	}

	/**
	 * Functions to create an OpenStreetMap HTML page
	 */

	private function generatePointsMap($points)
	{
		$html = "";
		$maxlat = -999.0;
		$minlat = 999.0;
		$maxlon = -999.0;
		$minlon = 999.0;

		foreach($points as $point)
		{
			$lat = $point['lat'];
			$lon = $point['lon'];
			$html .= "lonLat.push(new OpenLayers.LonLat(" . $lon . "," . $lat . ").transform(new OpenLayers.Projection(\"EPSG:4326\"),map.getProjectionObject()));\n";
			if($lat < $minlat) { $minlat = $lat; }
			if($lat > $maxlat) { $maxlat = $lat; }
			if($lon < $minlon) { $minlon = $lon; }
			if($lon > $maxlon) { $maxlon = $lon; }
		}

		$avelat = (($maxlat - $minlat) / 2) + $minlat;
		$avelon = (($maxlon - $minlon) / 2) + $minlon;

		$html = "<html><head><script src=\"http://www.openlayers.org/api/OpenLayers.js\"></script>\n<script>\nfunction drawMap() { map = new OpenLayers.Map(\"mapdiv\");\nmap.addLayer(new OpenLayers.Layer.OSM());\nvar lonLat = Array();\n" . $html;

		$html .= "var markers = new OpenLayers.Layer.Markers( \"Markers\" );\n";
		$html .= "var length = lonLat.length;\n";
		$html .= "map.addLayer(markers);\n";
		$html .= "for(var i=0; i < length; i++) { markers.addMarker(new OpenLayers.Marker(lonLat[i]));}\n";
		$html .= "var ctr = new OpenLayers.LonLat(" . $avelon . "," . $avelat . ").transform(new OpenLayers.Projection(\"EPSG:4326\"),map.getProjectionObject());\n";

		$html .= "var bounds = new OpenLayers.Bounds(" . $minlon . "," . $minlat . "," . $maxlon . "," . $maxlat . ").transform(new OpenLayers.Projection(\"EPSG:4326\"),new OpenLayers.Projection(\"EPSG:900913\"));\n";

		$html .= "var zoom = map.getZoomForExtent(bounds.transform(new OpenLayers.Projection(\"EPSG:4326\")), true);\n";
		$html .= "map.setCenter(ctr,zoom);\n";

		$html .= "}</script></head><body onLoad=\"drawMap();\"><div id=\"mapdiv\"></div></body></html>";

		return($html);

	}
	
	public function toOpenStreetMap()
	{
		$uri = $this->firstGraphURI;
		$doc = $this->resource( $uri );
		$objects = $this->allSubjects();
		$points = array();
		foreach( $objects as $thing )
		{
			if(!(($thing->has("http://www.w3.org/2003/01/geo/wgs84_pos#lat")) & ($thing->has("http://www.w3.org/2003/01/geo/wgs84_pos#long"))))
			{
				continue;
			}
			$point = array();
			$point['lat'] = $thing->getString("http://www.w3.org/2003/01/geo/wgs84_pos#lat");
			$point['lon'] = $thing->getString("http://www.w3.org/2003/01/geo/wgs84_pos#long");
			$point['title'] = $thing->label();
			$points[] = $point;
		}
		if(count($points) == 0)
		{
			return "";
		}
		return $this->generatePointsMap($points);
	}
	 
	/**
	 * Returns a serialization of every geo-locatable entity as KML
	 */
	public function toKml()
	{
		$uri = $this->firstGraphURI;
		$doc = $this->resource( $uri );

		$desc = "";

		$title = "Converted from RDF Document";
		if( $doc->hasLabel() )
		{
			$title = $doc->label()." ($title)";
		}

		if( $doc->has("dc:description") )
		{
			$desc.= $doc->get( "dc:description" )->toString()."\n\n";
		}
		if(strlen($uri) > 0)
		{
			$desc .= 'Converted from '.$uri.' using Graphite (http://graphite.ecs.soton.ac.uk/)';
		}
		else
		{
			$desc .= 'Converted to KML using Graphite (http://graphite.ecs.soton.ac.uk/)';
		}

		$kml = "";
		$kml .= '<?xml version="1.0" encoding="UTF-8"?>';
		$kml .= '<kml xmlns="http://www.opengis.net/kml/2.2">';
		$kml .= '<Document>';
		$kml .= '<name>'.htmlspecialchars($title).'</name>';
		$kml .= '<description>'.htmlspecialchars($desc).'</description>';

		$i=1;
		$objects = $this->allSubjects();
		$to_sort = array();
		foreach( $objects as $thing )
		{
			$title = $thing->toString();
			if( $thing->hasLabel() )
			{
				$title = $thing->label();
			}
			elseif( $thing->has( "-gr:hasPOS" ) && $thing->get( "-gr:hasPOS" )->hasLabel() )
			{
				$title = $thing->get( "-gr:hasPOS" )->label();
			}

			$desc='';
			$done = array();
			foreach( $thing->all("foaf:homepage") as $url )
			{
				if( @$done[$url->toString()] ) { continue; }
				$desc.="<div><a href='".$url->toString()."'>Homepage</a></div>";
				$done[$url->toString()] = true;
			}
			foreach( $thing->all("foaf:page") as $url )
			{
				if( @$done[$url->toString()] ) { continue; }
				$desc.="<div><a href='".$url->toString()."'>More Information</a></div>";
				$done[$url->toString()] = true;
			}

			if( $thing->has( "dc:description", "dcterms:description" ) )
			{
				$desc.= htmlspecialchars( $thing->getString( "dc:description" , "dcterms:description"));
			}

			$img = 'http://maps.gstatic.com/intl/en_ALL/mapfiles/ms/micons/blue-dot.png';
			if ( $thing->has("http://data.totl.net/ns/icon") )
			{
				$img = $thing->getString("http://data.totl.net/ns/icon");
			}
			if ( $thing->has("http://purl.org/openorg/mapIcon") )
			{
				$img = $thing->getString("http://purl.org/openorg/mapIcon");
			}
			$img = htmlspecialchars($img);
			$title = htmlspecialchars( $title );

			$placemark = "";
			if( $thing->has( "dct:spatial" ) )
			{
				$placemark = "<Placemark>";
				$placemark .= "<name>" . $title . " (Polygon)</name>";
				$placemark .= "<description>$desc</description>";
				foreach( $thing->all( "dct:spatial" ) as $sp )
				{
					$v = $sp->toString();
					if( preg_match( '/POLYGON\s*\(\((.*)\)\)/', $v, $bits ) )
					{
						$x = "";
						if( @$_GET['height'] )
						{
							$x = "<extrude>1</extrude><altitudeMode>relativeToGround</altitudeMode>";

						}
						$placemark .= "<Polygon>" . $x;
						$placemark .= "<outerBoundaryIs>";
						$placemark .= "<LinearRing>";
						$placemark .= "<tessellate>1</tessellate>";
						$placemark .= "<coordinates>";
						$coords = preg_split( '/\s*,\s*/', trim( $bits[1] ) );
						foreach( $coords as $coord )
						{
							$point = preg_split( '/\s+/', $coord );
							if(sizeof($point)==2) {
								if( @$_GET['height'] )
								{
									$point []= $_GET['height'];
								}
								else
								{
									$point []= "0.000000";
								}
							}
							$placemark.=join( ",",$point )."\n";
						}
						$placemark .= "</coordinates>";
						$placemark .= "</LinearRing>";
						$placemark .= "</outerBoundaryIs>";
						$placemark .= "</Polygon>";
					}
				}
				$placemark .= "<styleUrl>#stylep" . $i . "</styleUrl>";
				$placemark .= "</Placemark>";
				$placemark .= "<Style id='stylep" . $i . "'>";
				$placemark .= "<LineStyle>";
				$placemark .= "<color>ff000000</color>";
				$placemark .= "</LineStyle>";
				$placemark .= "<PolyStyle>";
				$placemark .= "<color>66fc3135</color>";
				$placemark .= "</PolyStyle>";
				$placemark .= "</Style>";
				$to_sort[$title." (Polygon)"][] = $placemark;
				++$i;
			}

			$alt = "0.000000";
			$lat = null;
			$long = null;
			if( $thing->has( "geo:lat" ) ) { $lat = $thing->getString( "geo:lat" ); }
			if( $thing->has( "geo:long" ) ) { $long = $thing->getString( "geo:long" ); }
			if( $thing->has( "geo:alt" ) ) { $alt = $thing->get( "geo:alt" ); }

			if( $thing->has( "vcard:geo" ) )
			{
				$geo = $thing->get( "vcard:geo" );
				if( $geo->has( "vcard:latitude" ) ) { $lat = $geo->getString( "vcard:latitude" ); }
				if( $geo->has( "vcard:longitude" ) ) { $long = $geo->getString( "vcard:longitude" ); }
			}
			if( $thing->has( "georss:point" ) )
			{
				list($lat,$long) = preg_split( '/ /', trim( $thing->get("georss:point" )->toString() ) );
			}

			if( isset( $lat ) && isset( $long ) )
			{
				$placemark = "<Placemark>";
				$placemark .= "<name>$title</name>";
				$placemark .= "<description>$desc</description>";
				$placemark .= "<styleUrl>#style" . $i . "</styleUrl>";
				$placemark .= "<Point>";
				$placemark .= "<coordinates>" . $long . "," . $lat . "," . $alt . "</coordinates>";
				$placemark .= "</Point>";
				$placemark .= "</Placemark>";
				$placemark .= "<Style id='style" . $i . "'>";
				$placemark .= "<IconStyle>";
				$placemark .= "<Icon>";
				$placemark .= "<href>$img</href>";
				$placemark .= "</Icon>";
				$placemark .= "</IconStyle>";
				$placemark .= "</Style>";
				++$i;
				$to_sort[$title][] = $placemark;
			}
		}

		ksort( $to_sort );
		foreach( $to_sort as $k=>$v )
		{
			$kml .= join( "", $v );
		}
		$kml .= "</Document></kml>";

		return($kml);

	}

	/**
	 * Returns the serialization of the entire RDF graph in memory using one of Arc2's serializers. By default the RDF/XML serializer is used, but others (try passing "Turtle" or "NTriples") can be used - see the Arc2 documentation.
	 */
	public function serialize( $type = "RDFXML" )
	{
		$ns = $this->ns;
		unset( $ns["dct"] ); 
		// use dcterms for preference. duplicates seem to cause
		// bugs in the serialiser
		$serializer = ARC2::getSer( $type, array( "ns" => $ns ));
		return $serializer->getSerializedTriples( $this->toArcTriples() );
	}

	public function cleanURI( $uri )
	{
		if( !$uri ) { return; }
		return preg_replace( '/^(https?:\/\/[^:\/]+):80\//','$1/', $uri );
	}

	/**
	 * Utility method (shamelessly ripped off from EasyRDF). Returns the primary topic of the first URL that was loaded. Handy when working with FOAF.
	 */
	public function primaryTopic( $uri = null )
	{
		if( !$uri ) { $uri = $this->firstGraphURI; }
		if( !$uri ) { return new Graphite_Null($this->g); }

		return $this->resource( Graphite::asString($uri) )->get( "foaf:primaryTopic", "-foaf:isPrimaryTopicOf" );
	}

	/**
	 * Add an additional namespace alias to the Graphite instance.
	 *
	 * @param string $short Must be a valid xmlns prefix. urn, http, doi, https, ftp, mail, xmlns, file and data are reserved.
	 * @param string $long  Must be either a valid URI or an empty string.
	 *
	 * @todo URI validation.
	 * @see http://www.w3.org/TR/REC-xml-names/#ns-decl
	 * @throws InvalidArgumentException
	 */
	public function ns( $short, $long )
	{
		if (empty($short)) {
			throw new InvalidArgumentException("A valid xmlns prefix is required.");
		}

		if( preg_match( '/^(urn|doi|http|https|ftp|mailto|xmlns|file|data)$/i', $short ) )
		{
			throw new InvalidArgumentException("Setting a namespace called '$short' is just asking for trouble. Abort.");
		}
		$this->ns[$short] = $long;
	}

	/**
	 * Get the resource with given URI. $uri may be abbreviated to "namespace:value".
	 *
	 * @return Graphite_Resource
	 */
	public function resource( $uri )
	{
		$uri = $this->expandURI( Graphite::asString($uri) );
		return new Graphite_Resource( $this, $uri );
	}

	/**
	 * Return a list of all resources loaded, with the rdf:type given. eg. $graph-&gt;allOfType( "foaf:Person" )
	 */
	public function allOfType( $uri )
	{
		return $this->resource( $uri )->all("-rdf:type");
	}

	/**
	 * Translate a URI from the long form to any shorthand version known.
	 * IE: http://xmlns.com/foaf/0.1/knows => foaf:knows
	 */
	public function shrinkURI( $uri )
	{
		if( Graphite::asString($uri) == "" ) { return "* This Document *"; }
		foreach( $this->ns as $short=>$long )
		{
			if( substr( Graphite::asString($uri), 0, strlen($long) ) == $long )
			{
				$term = substr( Graphite::asString($uri), strlen($long ) );
				if( ! preg_match( "/[#\/]/", $term ) )
				{
					return $short.":".$term;
				}
			}
		}
		return Graphite::asString($uri);
	}

	/**
	 * Translate a URI from the short form to any long version known.
	 * IE:  foaf:knows => http://xmlns.com/foaf/0.1/knows
	 * also expands "a" => http://www.w3.org/1999/02/22-rdf-syntax-ns#type
	 */
	public function expandURI( $uri )
	{
		# allow "a" as even shorter cut for rdf:type. This doesn't get applied
		# in inverse if you use shrinkURI
		if( $uri == "a" )
		{
			return "http://www.w3.org/1999/02/22-rdf-syntax-ns#type";
		}
		if( preg_match( '/:/', Graphite::asString($uri) ) )
		{
			list( $ns, $tag ) = preg_split( "/:/", Graphite::asString($uri), 2 );
			if( isset($this->ns[$ns]) )
			{
				return $this->ns[$ns].$tag;
			}
		}
		return Graphite::asString($uri);
	}

	/**
	 * Return a list of all resources in the graph which are the subject of at least one triple.
	 */
	public function allSubjects()
	{
		$r = new Graphite_ResourceList( $this );
		foreach( $this->t["sp"] as $subject_uri=>$foo )
		{
			 $r[] = new Graphite_Resource( $this, $subject_uri );
		}
		return $r;
	}

	/**
	 * Return a list of all resources in the graph which are the object of at least one triple.
	 */
	public function allObjects()
	{
		$r = new Graphite_ResourceList( $this );
		foreach( $this->t["op"] as $object_uri=>$foo )
		{
			 $r[] = new Graphite_Resource( $this, $object_uri );
		}
		return $r;
	}

	/**
	 * Create a pretty HTML dump of the current resource. Handy for debugging halfway through hacking something.
	 *
	 * $options is an optional array of flags to modify how dump() renders HTML. dumpText() does the same think with ASCII indention instead of HTML markup, and is intended for debugging command-line scripts.
	 *
	 * "label"=> 1 - add a label for the URI, and the rdf:type, to the top of each resource box, if the information is in the current graph.
	 * "labeluris"=> 1 - when listing the resources to which this URI relates, show them as a label, if possible, rather than a URI. Hovering the mouse will still show the URI.</div>
	 * "internallinks"=> 1 - instead of linking directly to the URI, link to that resource's dump on the current page (which may or may not be present). This can, for example, make bnode nests easier to figure out.
	 */
	public function dump( $options=array() )
	{
		$r = array();
		foreach( $this->t["sp"] as $subject_uri=>$foo )
		{
			$subject = new Graphite_Resource( $this, $subject_uri );
			$r []= $subject->dump($options);
		}
		return join("",$r );
	}

	/**
	 * @see dump()
	 */
	public function dumpText( $options=array() )
	{
		$r = array();
		foreach( $this->t["sp"] as $subject_uri=>$foo )
		{
			$subject = new Graphite_Resource( $this, $subject_uri );
			$r []= $subject->dumpText($options);
		}
		return join("\n",$r );
	}

    /** @deprecated All graphite objects should implement __toString() */
	public function forceString( &$uri )
	{
		$uri = asString( $uri );
		return $uri;
	}
	
	static public function asString( $uri )
	{
		if( is_object( $uri ) ) { return $uri->toString(); }
		return (string)$uri;
	}
}

require_once 'Graphite/Node.php';
require_once 'Graphite/Null.php';
require_once 'Graphite/Literal.php';
require_once 'Graphite/Resource.php';
require_once 'Graphite/Relation.php';
require_once 'Graphite/InverseRelation.php';
require_once 'Graphite/ResourceList.php';
require_once 'Graphite/Description.php';
require_once 'Graphite/ParserSPARQLPath.php';

function graphite_sort_list_cmp( $a, $b )
{
	global $graphite_sort_args;

	foreach( $graphite_sort_args as $arg )
	{
		$va = $a->get( $arg );
		$vb = $b->get( $arg );
		if($va < $vb) return -1;
		if($va > $vb) return 1;
	}
	return 0;
}





