<?php
# (c)2010,2011 Christopher Gutteridge / University of Southampton
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


class Graphite
{

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

		$this->loaded = array();
		$this->debug = false;

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
			$this->forceString( $uri );
			$this->load( $uri );
		}

		$this->bnodeprefix = 0;
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

	public function setDebug( $boolean ) { $this->debug = $boolean; }

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
		$this->forceString( $uri );
		$uri = $this->expandURI( $uri );

		if( substr( $uri,0,5 ) == "data:" )
		{
			$data = urldecode( preg_replace( "/^data:[^,]*,/","", $uri ) );
			$parser = ARC2::getTurtleParser();
			$parser->parse( $uri, $data );
		}
		else
		{
			if( $this->loaded( $uri ) !== false ) { return $this->loaded( $uri ); }
			if( isset($this->cacheDir) )
			{
				$filename = $this->cacheDir."/".md5( $this->removeFragment( $uri ) );

				if( !file_exists( $filename ) || filemtime($filename)+$this->cacheAge < time() )
				{
					# decache if out of date, even if we fail to re cache.
					if( file_exists( $filename ) ) { unlink( $filename ); }
					$url = $uri;
					$ttl = 16;
					$mime = "";
					$old_user_agent = ini_get('user_agent');
					ini_set('user_agent', "PHP\r\nAccept: application/rdf+xml");
					while( $ttl > 0 )
					{
						$ttl--;
						# dirty hack to set the accept header without using curl
						if( !$rdf_fp = fopen($url, 'r') ) { break; }
						$meta_data = stream_get_meta_data($rdf_fp);
						$redir = 0;
						if( @!$meta_data['wrapper_data'] )
						{
							fclose($rdf_fp);
							continue;
						}
						foreach($meta_data['wrapper_data'] as $response)
						{
							if (substr(strtolower($response), 0, 10) == 'location: ')
							{
								$newurl = substr($response, 10);
								if( substr( $newurl, 0, 1 ) == "/" )
								{
									$parts = preg_split( "/\//",$url );
									$newurl = $parts[0]."//".$parts[2].$newurl;
								}
								$url = $newurl;
								$redir = 1;
							}
							if (substr(strtolower($response), 0, 14) == 'content-type: ')
							{
								$mime = preg_replace( "/\s*;.*$/","", substr($response, 14));
							}
						}
						if( !$redir ) { break; }
					}
					ini_set('user_agent', $old_user_agent);
					if( $ttl > 0 && $mime == "application/rdf+xml" && $rdf_fp )
					{
						# candidate for caching!
						if (!$cache_fp = fopen($filename, 'w'))
						{
							echo "Cannot write file ($filename)";
							exit;
						}

						while (!feof($rdf_fp)) {
							fwrite( $cache_fp, fread($rdf_fp, 8192) );
						}
						fclose($cache_fp);
					}
					@fclose($rdf_fp);
				}

			}
			if( isset( $filename ) &&  file_exists( $filename ) )
			{
				$parser = ARC2::getRDFXMLParser();
				$parser->parse( $uri, file_get_contents($filename) );
			}
			else
			{
				$opts = array();
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
	function loadSPARQL( $endpoint, $query )
	{
		return $this->load( $endpoint."?query=".urlencode($query) );
	}

	/**
	 * Take a base URI and a string of turtle RDF and load the new triples into the graph. Return the number of triples loaded.
	 */
	function addTurtle( $base, $data )
	{
		$parser = ARC2::getTurtleParser();
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
		$parser = ARC2::getRDFXMLParser();
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
	function addCompressedTriple( $s,$p,$o,$o_datatype=null,$o_lang=null,$aliases=array() )
	{
		$s = $this->expandURI( $s );
		$p = $this->expandURI( $p );
		$o = $this->expandURI( $o );
		if( isset( $o_datatype ) && $o_dataype != "literal" )
		{
			$o_datatype = $this->expandURI( $o_datatype );
		}
		$this->addTriple( $s,$p,$o,$o_datatype,$o_lang,$aliases );
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
			$this->t["sp"][$s][$p][] = array(
				"v"=>$o,
				"d"=>$o_datatype,
				"l"=>$o_lang );
		}
		else
		{
			$this->t["sp"][$s][$p][] = $o;
		}
		$this->t["op"][$o][$p][] = $s;
	}

	/**
	 * Returns all triples of which this resource is the subject in Arc2's internal triples format.
	 */
	public function toArcTriples()
	{
		$arcTriples = array();
		foreach( $this->allSubjects() as $s )
		{
			$arcTriples = array_merge( $arcTriples, $s->toArcTriples( false ) );
		}
		return $arcTriples;
	}

	/**
	 * Returns the serialization of the entire RDF graph in memory using one of Arc2's serializers. By default the RDF/XML serializer is used, but others (try passing "Turtle" or "NTriples") can be used - see the Arc2 documentation.
	 */
	public function serialize( $type = "RDFXML" )
	{
		$serializer = ARC2::getSer( $type, array( "ns" => $this->ns ) );
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
		$this->forceString( $uri );

		return $this->resource( $uri )->get( "foaf:primaryTopic", "-foaf:isPrimaryTopicOf" );
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
		$this->forceString( $uri );
		$uri = $this->expandURI( $uri );
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
		$this->forceString( $uri );
		if( $uri == "" ) { return "* This Document *"; }
		foreach( $this->ns as $short=>$long )
		{
			if( substr( $uri, 0, strlen($long) ) == $long )
			{
				return $short.":".substr( $uri, strlen($long ));
			}
		}
		return $uri;
	}

	/**
	 * Translate a URI from the short form to any long version known.
	 * IE:  foaf:knows => http://xmlns.com/foaf/0.1/knows
	 */
	public function expandURI( $uri )
	{
		$this->forceString( $uri );
		if( preg_match( '/:/', $uri ) )
		{
			list( $ns, $tag ) = preg_split( "/:/", $uri, 2 );
			if( isset($this->ns[$ns]) )
			{
				return $this->ns[$ns].$tag;
			}
		}
		return $uri;
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

	public function forceString( &$uri )
	{
		if( is_object( $uri ) ) { $uri = $uri->toString(); }
		return $uri;
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





