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


class Graphite
{
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

	public function freeze( $filename )
	{
		$fh = fopen($filename, 'w') or die("can't open file");
		fwrite($fh, serialize( $this ) );
		fclose($fh);
	}

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

	public function labelRelations( $new = null )
	{
		$lr = $this->labelRelations;
		if( isset( $new ) ) { $this->labelRelations = $new; }
		return $lr;
	}
	public function addLabelRelation( $addition )
	{
		$this->labelRelations []= $addition;
	}

	public function mailtoIcon( $new = null )
	{
		$icon = $this->mailtoIcon;
		if( isset( $new ) ) { $this->mailtoIcon = $new; }
		return $icon;
	}

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

	function loadSPARQL( $endpoint, $query )
	{
		return $this->load( $endpoint."?query=".urlencode($query) );
	}

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

	function addBnodePrefix( $uri ) 
	{
		return preg_replace( "/^_:/", "_:g" . $this->bnodeprefix . "-", $uri );
	}

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

	function addTriple( $s,$p,$o,$o_datatype=null,$o_lang=null,$aliases=array() )
	{
		if( $this->workAround4StoreBNodeBug )
		{
			if( $s == "_:NULL" || $o == "_:NULL" ) { return; } 
		}
		$s = $this->addBnodePrefix( $this->cleanURI( $s ) );
		if( $o_datatype != "literal" )
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

	public function toArcTriples()
	{
		$arcTriples = array();
		foreach( $this->allSubjects() as $s )
		{
			$arcTriples = array_merge( $arcTriples, $s->toArcTriples( false ) );
		}
		return $arcTriples;
	}

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

	public function primaryTopic( $uri = null )
	{
		if( !$uri ) { $uri = $this->firstGraphURI; }
		if( !$uri ) { return new Graphite_Null($this->g); }
		$this->forceString( $uri );

		return $this->resource( $uri )->get( "foaf:primaryTopic", "-foaf:isPrimaryTopicOf" );
	}

	public function ns( $short, $long )
	{
		if( preg_match( '/^(urn|doi|http|https|ftp|mailto|xmlns|file|data)$/', $short ) )
		{
			print "<ul><li>Setting a namespace called '$short' is just asking for trouble. Abort.</li></ul>";
			exit;
		}
		$this->ns[$short] = $long;
	}

	public function resource( $uri )
	{
		$this->forceString( $uri );
		$uri = $this->expandURI( $uri );
		return new Graphite_Resource( $this, $uri );
	}

	public function allOfType( $uri )
	{
		return $this->resource( $uri )->all("-rdf:type");
	}

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


	public function allSubjects()
	{
		$r = new Graphite_ResourceList( $this );
		foreach( $this->t["sp"] as $subject_uri=>$foo )
		{
			 $r[] = new Graphite_Resource( $this, $subject_uri );
		}
		return $r;
	}

	public function allObjects()
	{
		$r = new Graphite_ResourceList( $this );
		foreach( $this->t["op"] as $object_uri=>$foo )
		{
			 $r[] = new Graphite_Resource( $this, $object_uri );
		}
		return $r;
	}

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

class Graphite_Node
{
	function __construct( $g )
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
class Graphite_Null extends Graphite_Node
{
	function nodeType() { return "#null"; }
	function isNull() { return true; }
}
class Graphite_Literal extends Graphite_Node
{
	function __construct( $g, $triple )
	{
		$this->g = $g;
		$this->triple = $triple;
		$this->v = $triple["v"];
	}

	function __toString() { return $this->triple["v"]; }
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
		$v = preg_replace( "/  +/e", "\"<span class='special_char' style='font-size:70%'>\".str_repeat(\"␣\",strlen(\"$0\")).\"</span>\"", $v );
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

class Graphite_Resource extends Graphite_Node
{
	function __construct( $g, $uri )
	{
		$this->g = $g;
		$this->g->forceString( $uri );
		$this->uri = $uri;
	}

	public function get( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return new Graphite_Null($this->g); }
		return $l[0];
	}

	public function getLiteral( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return; }
		return $l[0]->toString();
	}
	# getString deprecated in favour of getLiteral 
	public function getString( /* List */ ) { return $this->getLiteral( func_get_args() ); }

	public function getDatatype( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return; }
		return $l[0]->datatype();
	}
	public function getLanguage( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = $this->all( $args );
		if( sizeof( $l ) == 0 ) { return; }
		return $l[0]->language();
	}

	public function allString( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

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
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

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
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }
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
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

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

	function __toString() { return $this->uri; }
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
}

class Graphite_Relation extends Graphite_Resource
{
	function nodeType() { return "#relation"; }
}

class Graphite_InverseRelation extends Graphite_Relation
{
	function nodeType() { return "#inverseRelation"; }
}
class Graphite_ResourceList extends ArrayIterator
{

	function __construct( $g, $a=array() )
	{
		$this->g = $g;
		$this->a = $a;
		if( $a instanceof Graphite_ResourceList )
		{
			print "<li>Graphite warning: passing a Graphite_ResourceList as the array passed to new Graphite_ResourceList will make weird stuff happen.</li>";
		}
		parent::__construct( $this->a );
	}


	function join( $str )
	{
		$first = 1;
		$l = array();
		foreach( $this as $resource )
		{
			if( !$first ) { $l []= $str; }
			$this->g->forceString( $resource );
			$l []= $resource;
			$first = 0;
		}
		return join( "", $l );
	}

	function dump()
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->dump();
		}
		return join( "", $l );
	}

	public function duplicate()
	{
		$l = array();
		foreach( $this as $resource ) { $l []= $resource; }
		return new Graphite_ResourceList($this->g,$l);
	}

	public function sort( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		global $graphite_sort_args;
		$graphite_sort_args = array();
		foreach( $args as $arg )
		{
			if( $arg instanceof Graphite_Resource ) { $arg = $arg->toString(); }
			$graphite_sort_args [] = $arg;
		}

		$l = array();
		foreach( $this as $resource ) { $l []= $resource; }
		usort($l, "graphite_sort_list_cmp" );
		return new Graphite_ResourceList($this->g,$l);
	}

	public function uasort( $cmp )
	{
		usort($this->a, $cmp );
	}

	public function get( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->get( $args );
		}
		return new Graphite_ResourceList($this->g,$l);
	}


	
	public function getLiteral( /* List */)
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->getLiteral( $args );
		}
		return new Graphite_ResourceList($this->g,$l);
	}
	# getString deprecated in favour of getLiteral 
	public function getString( /* List */ ) { return $this->getLiteral( func_get_args() ); }

	public function label()
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->label();
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	public function link() 
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->link();
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	public function prettyLink() 
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->prettyLink();
		}
		return new Graphite_ResourceList($this->g,$l);
	}
	

	public function load()
	{
		$n = 0;
		foreach( $this as $resource )
		{
			$n += $resource->load();
		}
		return $n;
	}

	public function allString( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }
		$l = array();
		$done = array();
		foreach( $this as $resource )
		{
			$all = $resource->all( $args );
			foreach( $all as $to_add )
			{
				if( isset($done[$to_add->toString()]) ) { continue; }
				$l []= $to_add->toString();
				$done[$to_add->toString()] = 1;
			}
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	public function all( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }
		$l = array();
		$done = array();
		foreach( $this as $resource )
		{
			$all = $resource->all( $args );
			foreach( $all as $to_add )
			{
				if( isset($done[$to_add->toString()]) ) { continue; }
				$l []= $to_add;
				$done[$to_add->toString()] = 1;
			}
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	function append( $x /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$list = $this->duplicate();
		foreach( $args as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			$list [] = $arg;
		}
		return $list;
	}

	function distinct()
	{
		$l= array();
		$done = array();
		foreach( $this as $resource )
		{
			if( isset( $done[$resource->toString()] ) ) { continue; }
			$l [] = $resource;
			$done[$resource->toString()]=1;
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	function union( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$list = new Graphite_ResourceList($this->g);
		$done = array();
		foreach( $this as $resource )
		{
			if( isset( $done[$resource->toString()] ) ) { continue; }
			$list [] = $resource;
			$done[$resource->toString()]=1;
		}
		foreach( $args as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			if( isset( $done[$arg->toString()] ) ) { continue; }
			$list [] = $arg;
			$done[$arg->toString()]=1;
		}
		return $list;
	}

	function intersection( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$list = new Graphite_ResourceList($this->g,array());
		$seen = array();
		foreach( $this as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			$seen[$arg->toString()]=1;
		}
		foreach( $args as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			if( ! isset($seen[$arg->toString()]) ) { continue; }
			$list [] = $arg;
		}
		return $list;
	}

	function except( /* List */ )
	{
		$args = func_get_args();
		if( $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$list = new Graphite_ResourceList($this->g,array());
		$exclude = array();
		foreach( $args as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			$exclude[$arg->toString()]=1;
		}
		foreach( $this as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			if( isset($exclude[$arg->toString()]) ) { continue; }
			$list [] = $arg;
		}
		return $list;
	}

	function allOfType( $uri )
	{
		$list = new Graphite_ResourceList( $this->g, array() );
		foreach( $this as $item )
		{
			if( $item->isType( $uri ) )
			{
				$list [] = $item;
			}
		}
		return $list;
	}
}

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
					$json[$jsonkey][] = $value->toString();
				}
				else
				{	
					$subjson = array();
					$uri = $value->toString();
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
					$new_graph->addTriple( 
						$resource->toString(),
						$relation->toString(),
						$value->toString(),
						$value->datatype(),
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
							$resource->toString(),
							$relation->toString(),
							$value->toString() );
					}
					else
					{
						$new_graph->addTriple( 
							$value->toString(),
							$relation->toString(),
							$resource->toString() );
					}
				}
			}
		}
	}

	function loadSPARQL( $endpoint, $debug = false )
	{
		$unionbits = array();
		$conbits = array();
		$unionbits = $this->_toSPARQL( $this->tree, "", null, "", $conbits );
		$n = 0;
		foreach( $unionbits as $unionbit )
		{
			$sparql = "CONSTRUCT { ".join( " . ", $conbits )." } WHERE { $unionbit }";
			if( $debug || @$_GET["_graphite_debug"] ) { 
				print "<tt>\n\n".htmlspecialchars($sparql)."</tt>\n\n";
			}
			$n+=$this->graph->loadSPARQL( $endpoint, $sparql );
		}
		return $n;
	}

	function _toSPARQL($tree, $suffix, $in_dangler = null, $sparqlprefix = "", &$conbits )
	{
		$unionbits = array();
		if( !isset( $in_dangler ) )
		{
			$in_dangler = "<".$this->resource->toString().">";
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

				$sparql = "$sparqlprefix $sub $pre $obj .";
				if( isset( $routes["*"] ) )
				{
					$bits_from_routes = $this->_toSPARQL( $routes["*"], $suffix."_".$i, $out_dangler, "", $conbits );
					$i++;
					foreach( $bits_from_routes as $bit )
					{
						$sparql .= " OPTIONAL { $bit }";
					}
				}
				$unionbits []= $sparql;
				$conbits []= "$sub $pre $obj";

				foreach( $routes as $pred=>$route )
				{
					if( $pred == "*" ) { continue; }

					$pre = "<".$this->graph->expandURI( $pred ).">";

					$bits_from_routes = $this->_toSPARQL( $route, $suffix."_".$i, $out_dangler, "$sparqlprefix $sub $pre $obj .", $conbits );
					$i++;
					foreach( $bits_from_routes as $bit )
					{
						$unionbits []= $bit;
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

					$bits_from_routes = $this->_toSPARQL( $routes[$pred],$suffix."_".$i, $out_dangler, "", $conbits );
					$i++;

					$sparql = "$sparqlprefix $sub $pre $obj .";
					foreach( $bits_from_routes as $bit )
					{
						$sparql .= " OPTIONAL { $bit }";
					}

					$unionbits []= $sparql;
					$conbits []= "$sub $pre $obj";

				}
			}
		}

		return $unionbits;
	} # end _toSPARQL

	function getFormats()
	{
		return array(
			"json"=>"JSON",
			"nt"=>"RDF (Triples)",
			"ttl"=>"RDF (Turtle)",
			"rdf"=>"RDF (XML)",
			"rdf.html" => "RDF (RDF HTML Debug)",
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


