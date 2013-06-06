<?php


require_once( "arc/ARC2.php" );
require_once( "Graphite/Graphite.php" );

# uppercase = path or atom
# lowercase = atom
# a|b|c|d
# (A|B)|C = A|B|C
# a|. = .
# ^(A|B) = ^A|^B
# ^(A/B) = ^B/^A
# atom is 
#  a
#  !(a)
#  !(a|b|c)

$ns = array(
"rdfs"=> 	"http://www.w3.org/2000/01/rdf-schema#",
"xsd"=> 	"http://www.w3.org/2001/XMLSchema#",
"sioc"=> 	"http://rdfs.org/sioc/ns#",
"dcterms"=> 	"http://purl.org/dc/terms/",
"prog"=> 	"http://purl.org/prog/",
"foaf"=> 	"http://xmlns.com/foaf/0.1/",
"tl"=> 		"http://purl.org/NET/c4dm/timeline.owl#",
"event"=> 	"http://purl.org/NET/c4dm/event.owl#",
);

$path = "(./(a|rdfs:label))|((!^event:place)/(a|rdfs:label))";
print "\n";
print "PATH: $path\n\n";
$p = new sparqlPathParser( 
	array( "hyphen-inverse"=>true, "wildcards"=>true )
);
$p->setString( $path );
list( $match, $offset ) = $p->xPath( 0 );
if( !$match || $offset != sizeof( $p->chars ) ) { 
	print "fail!\n"; 
	exit;
}
print render( $match )."\n";
print "--\n";
$match = optimise1( $match, $ns );
print render( $match )."\n";
exit;

function render( $tree, $indent="" )
{
	if( $tree["type"] == "PNAME" )
	{	
		return $indent."pname(".$tree["ns"].":".$tree["local"].")\n";
	}
	if( $tree["type"] == "IRIREF" ) { return $indent."<".$tree["v"].">\n"; }
	if( $tree["type"] == "A" ) { return $indent."A\n"; }
	if( $tree["type"] == "ANY" ) { return $indent."ANY\n"; }

	if( $tree["type"] == "inv" ) { return $indent.$tree["type"]."\n".render($tree["v"],"$indent  "); }
	if( $tree["type"] == "NPS" ) { return $indent.$tree["type"]."\n".render($tree["v"],"$indent  "); }

	$v = array();
	foreach( $tree["v"] as $p )
	{
		$v[]=render( $p, "$indent  " );
	}
	return $indent.$tree["type"]."\n".join( "",$v);
}


function optimise1($tree,$ns)
{
	if( $tree["type"] == "ZeroOrMorePath" ) { throw new PathException( "ZeroOrMorePath (*) not supported" ); }
	if( $tree["type"] == "OneOrMorePath" ) { throw new PathException( "OneOrMorePath (+) not supported" ); }
	if( $tree["type"] == "ZeroOrOnePath" ) { throw new PathException( "ZeroOrOnePath (?) not supported" ); }

	if( $tree["type"] == "A" )
	{
		return array( "type"=>"IRIREF", "v"=>"http://www.w3.org/1999/02/22-rdf-syntax-ns#type" );
	}
	if( $tree["type"] == "PNAME" )
	{
		# look up ns later!! TODO
		if( !array_key_exists( $tree["ns"], $ns ) )
		{
			throw new PathException( "namespace '".$tree["ns"]."' not defined" );
		}
		$uri = $ns[$tree["ns"]].$tree["local"];
		return array( "type"=>"IRIREF", "v"=>$uri );
	}

	# remove doubled up ^^
	if( $tree["type"] == "inv" && $tree["v"]["type"] == "inv" )
	{
		return optimise1( $tree["v"]["v"],$ns ); 
	}

	# inv(alt(x,y)) becomes alt(inv(x),inv(y))
	if( $tree["type"] == "inv" && $tree["v"]["type"] == "alt" )
	{
		$v=array();
		foreach( $tree["v"]["v"] as $p )
		{
			$v []= array( "type"=>"inv", "v"=>$p );
		}
		return optimise1( array( "type"=>"alt", "v"=>$v ),$ns );
	}

	# inv(seq(x,y)) becomes seq(inv(y),inv(x))
	if( $tree["type"] == "inv" && $tree["v"]["type"] == "seq" )
	{
		$v=array();
		foreach( $tree["v"]["v"] as $p )
		{
			$v []= array( "type"=>"inv", "v"=>$p );
		}
		return optimise1( array( "type"=>"seq", "v"=>array_reverse($v ) ),$ns );
	}
	
	if( $tree["type"] == "NPS" )
	{
		return array( "type"=>"NPS", "v"=>optimise1( $tree["v"],$ns ) );
	}
	if( $tree["type"] == "inv" )
	{
		return array( "type"=>"inv", "v"=>optimise1( $tree["v"],$ns ) );
	}
		
	if( $tree["type"] == "seq" || $tree["type"] == "alt" )
	{
		$v = array();
		foreach( $tree["v"] as $p )
		{
			$new_p = optimise1( $p,$ns );
			# if the optimised version of this property is now the same type as the
			# node we are processing, add it's children directly to this nodes children
			if( $new_p["type"] == $tree["type"] )
			{
				foreach( $new_p["v"] as $inner_p ) { $v[]=$inner_p; }
			}
			else
			{
				#otherwise add it as normal
				$v[]=$new_p;
			}
		}		

		# if alt contains a ANY remove all IRIREF,
		# if alt contains a ^ANY remove all ^IRIREF
		if( $tree["type"] == "alt" )
		{
			$remove_iriref = false;
			$remove_inv_iriref = false;
			foreach( $v as $p )
			{
				if( $p["type"] == "ANY" ) { $remove_iriref = true; }
				if( $p["type"] == "inv" && $p["v"]["type"] == "ANY" ) { $remove_inv_iriref = true; }
			}
			$new_v = array();
			foreach( $v as $p )
			{
				if( $remove_iriref && $p["type"] == "IRIREF" ) { continue; }
				if( $remove_inv_iriref && $p["type"] == "inv" && $p["v"]["type"] == "IRIREF" ) { continue; }
				$new_v []= $p;
			}
			$v = $new_v;
		}

		# alt contains single value -> becomes single value
		# seq contains single value -> becomes single value
		if( sizeof( $v ) == 1 )
		{
			return $v[0];
		}

		return array( "type"=>$tree["type"], "v"=>$v );
	}

	return $tree;
}
		


class sparqlPathParser {

# defaults
var $options = array( 
	"hyphen-inverse" => false,
	"wildcards" => false,
);

function sparqlPathParser( $options = array() )
{
	foreach( $this->options as $k=>$v )
	{
		if( array_key_exists( $k, $options ) )
		{
			$this->options[ $k ] = $options[$k];
		}
		else
		{
			print "[[Unknown sparqlPathParser option: $k]]\n";
		}
	}
}

var $chars = false;
function setString( $str )
{
	preg_match_all('/./u', $str, $results);
	$this->chars = $results[0];
}

function xChar($re, $offset, $options = 's') 
{
 	$char = mb_substr($this->str, $offset, 1 );
	if( preg_match( "/^$re/".$options, $char ) )
	{
		return array( $char, $offset+1 );
	}
	return array( false, $offset );
}

# functions all return
# - return value or false
# - char offset after consumption of code which produced value

#[88]  	Path	  ::=  	PathAlternative
function xPath( $offset )
{
	return $this->xPathAlternative( $offset );
}

#[89]  	PathAlternative	  ::=  	PathSequence ( '|' PathSequence )*
function xPathAlternative( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPathSequence( $offset );
	if( !$sub_r ) { return array( false, $offset ); }
	$paths = array( $sub_r );
	while( $sub_offset < sizeof( $this->chars ) && $this->chars[ $sub_offset ] == "|" )
	{
		$sub_offset++;

		list( $sub_r, $sub_offset ) = $this->xPathSequence( $sub_offset );
		if( !$sub_r ) { return array( false, $offset ); }
		
		$paths []= $sub_r;
	}

	if( sizeof( $paths ) == 1 ) 
	{
		return array( $paths[0], $sub_offset );
	}
	
	return array( array( "type"=>"alt", "v"=>$paths ), $sub_offset );
}	


#[90]  	PathSequence	  ::=  	PathEltOrInverse ( '/' PathEltOrInverse )*
function xPathSequence( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPathEltOrInverse( $offset );
	if( !$sub_r ) { return array( false, $offset ); }

	$paths = array( $sub_r );
	while( $sub_offset < sizeof( $this->chars ) && $this->chars[ $sub_offset ] == "/" )
	{
		$sub_offset++;

		list( $sub_r, $sub_offset ) = $this->xPathEltOrInverse( $sub_offset );
		if( !$sub_r ) { return array( false, $offset ); }
		
		$paths []= $sub_r;
	}

	if( sizeof( $paths ) == 1 ) 
	{
		return array( $paths[0], $sub_offset );
	}
	
	return array( array( "type"=>"seq", "v"=>$paths ), $sub_offset );
}

#[91]  	PathElt	  ::=  	PathPrimary PathMod?
function xPathElt( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPathPrimary( $offset );
	if( !$sub_r ) { return array( false, $offset ); }

	list( $sub_r2, $sub_offset ) = $this->xPathMod( $sub_offset );
	
	if( $sub_r2 )
	{
		return array( array( "type"=>$sub_r2, "v"=>$sub_r ), $sub_offset );
	}

	return array( $sub_r, $sub_offset );
}
	

#[92]  	PathEltOrInverse	  ::=  	PathElt | '^' PathElt
function xPathEltOrInverse( $offset )
{
	$char = $this->chars[$offset];
	if( $char == "^" && !( $this->options["hyphen-inverse"] && $char=="-") ) 
	{
		$sub_offset = $offset+1;
		list( $sub_r, $sub_offset ) = $this->xPathElt( $sub_offset );
		if( !$sub_r ) { return array( false, $offset ); }
		
		return array( array( "type"=>"inv", "v"=>$sub_r ) , $sub_offset );
	}
	
	return $this->xPathElt( $offset );
}

#[93]  	PathMod	  ::=  	'?' | '*' | '+'
function xPathMod( $offset )
{
	if( $offset >= sizeof( $this->chars ) ) { return array( false, $offset ); }
	$char = $this->chars[$offset];
	if( $char == "*" )
	{
		return array( "ZeroOrMorePath", $offset+1 );
	}
	if( $char == "+" )
	{
		return array( "OneOrMorePath", $offset+1 );
	}
	if( $char == "?" )
	{
		return array( "ZeroOrOnePath", $offset+1 );
	}

	return array( false, $offset );
}

#[94]  	PathPrimary	  ::=  	iri | 'a' | '!' PathNegatedPropertySet | '(' Path ')'
# Minor hack to add '.' as another alternate to indicate any predicate. 
function xPathPrimary( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xiri( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

 	$char = $this->chars[$offset];
	if( $char == "a" ) 
	{ 
		return array( array( "type"=>"A" ), $offset+1 );
	}

	if( $this->options["wildcards"] && $char=="." ) 
	{
		return array( array( "type"=>"ANY" ), $offset+1 );
	}

	# '!' PathNegatedPropertySet 
	if( $char == "!" )
	{
		list( $sub_r, $sub_offset ) = $this->xPathNegatedPropertySet( $offset+1 );
		if( !$sub_r ) { return array( false, $offset ); } 

		return array( array( "type"=>"NPS", "v"=>$sub_r ), $sub_offset ); 
	}
	
	# '(' Path ')'
	if( $char == "(" )
	{
		list( $sub_r, $sub_offset ) = $this->xPath( $offset+1 );
		if( !$sub_r ) { return array( false, $offset ); }

		$char = $this->chars[$sub_offset];
		if( $char != ")" ) { return array( false, $offset ); }
		$sub_offset++;

		return array( $sub_r, $sub_offset ); 
	}

	# otherwise fail
	return array( false, $offset );
}

#[95]  	PathNegatedPropertySet	  ::=  	PathOneInPropertySet | '(' ( PathOneInPropertySet ( '|' PathOneInPropertySet )* )? ')'
function xPathNegatedPropertySet( $offset )
{
 	$char = $this->chars[$offset];
	# PathOneInPropertySet 
	if( $char != "(" )
	{
		list( $sub_r, $sub_offset ) = $this->xPathOneInPropertySet( $offset );
		if( $sub_r ) { return array( $sub_r, $sub_offset ); }
	
		return array( false, $offset );
	}

	# '(' ( PathOneInPropertySet ( '|' PathOneInPropertySet )* )? ')'
	$sub_offset = $offset+1; # consume "("

	# out of stuff to parse?
	if( $sub_offset >= sizeof( $this->chars ) ) { return array( false, $offset ); }

	$paths = array();

	# check for simple case of empty list
 	$char = $this->chars[$sub_offset];
	if( $char == ")" )	
	{
		return array( array( "type"=>"alt", "v"=>$paths ), $sub_offset+1 );
	}

	while( true )
	{
		# Parse a pathone
		list( $sub_r, $sub_offset ) = $this->xPathOneInPropertySet( $sub_offset );
		if( !$sub_r ) { return array( false, $offset ); }
		$paths []= $sub_r;	

		if( $sub_offset >= sizeof( $this->chars ) ) {return array( false, $offset ); }
 		$char = $this->chars[$sub_offset];
		if( $char == ")" )
		{
			return array( array( "type"=>"alt", "v"=>$paths ), $sub_offset+1 );
		}
	
		# if not a ")" then expect a "|" or fail
		if( $char == "|" )
		{
			$sub_offset++; # consume it
			continue;
		}
		return( array( false, $offset ) );
	}
}		

#[96]  	PathOneInPropertySet	  ::=  	iri | 'a' | '^' ( iri | 'a' ) 
function xPathOneInPropertySet( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xiri( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

 	$char = $this->chars[$offset];
	if( $char == "a" ) 
	{ 
		return array( array( "type"=>"A" ), $offset+1 );
	}

	# - is not a legal alternative to "^" but for back compatibility in graphite
	# we allow it if an option is set.	
	if( $char != "^" && !( $this->options["hyphen-inverse"] && $char=="-") ) { return array( false, $offset ); }
	$sub_offset = $offset+1;	

	list( $sub_r, $sub_offset2 ) = $this->xiri( $sub_offset );
	if( $sub_r ) { return array( array("type"=>"inv", "v"=>$sub_r), $sub_offset2 ); }

 	$char = $this->chars[$sub_offset];
	if( $char == "a" ) 
	{ 
		return array( array( "type"=>"inv", "v"=>array( "type"=>"A" )), $sub_offset+1 );
	}

	return array( false, $offset );	
}

############################################################
# the rest of these method parse URIs and prefixed names.
############################################################

#[136] 	iri		  ::=  	IRIREF | PrefixedName
function xiri( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xIRIREF( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

	list( $sub_r, $sub_offset ) = $this->xPrefixedName( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

	return array( false, $offset );
}

#[139]	IRIREF		  ::=  	'<' ([^<>"{}|^`\]-[#x00-#x20])* '>'
function xIRIREF( $offset )
{
 	$char = $this->chars[$offset];
	if( $char != "<" ) { return array( false, $offset ); }
	$sub_offset = $offset+1;

 	$char = $this->chars[$sub_offset];
	$matched = array();
	while( $char != ">" )
	{
		# if we run out of characters before we hit a ">"
		# then this did not match
		if( $sub_offset >= sizeof( $this->chars ) ) { return array( false, $offset ); }

	 	$char = $this->chars[$sub_offset];

		# an IRI ref can't contiain these charaters
		if( strpos( "<\"{}|^`\\", $char ) !== false ) { return array( false, $offset ); }

		$u = $this->mb_ord( $char );
		if( $u <= 0x20 ) { return array( false, $offset ); }

		if( $char != ">" )
		{
			$matched []= $char;
		}
		$sub_offset++;
	}

	return array( array( "type"=>"IRIREF", "v"=>join( "", $matched ) ), $sub_offset );
}

#[137]	PrefixedName	  ::=  	PNAME_LN | PNAME_NS
function xPrefixedName( $offset )
{
	# nb. this sub_r is a description of the prefixed name
	list( $sub_r, $sub_offset ) = $this->xPNAME_LN( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

	list( $sub_r, $sub_offset ) = $this->xPNAME_NS( $offset );
	if( $sub_r ) 
	{ 
		$namespace = $sub_r;
		$local = "";
		return array( array( "type"=>"PNAME", "ns"=>$namespace, "local"=>$local ), $sub_offset );
	}

	return array( false, $offset );
}

#[140]	PNAME_NS	  ::=  	PN_PREFIX? ':'
function xPNAME_NS( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPN_PREFIX( $offset );
	if( !$sub_r ) { return array( false, $offset ); }

	if( $sub_offset >= sizeof( $this->chars ) ) { return array( false, $offset ); }
 	$char = $this->chars[$sub_offset];
	if( $char != ":" ) { return array( false, $offset ); }

	return array( $sub_r, $sub_offset+1 );
}

	
#[141]	PNAME_LN	  ::=  	PNAME_NS PN_LOCAL
function xPNAME_LN( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPNAME_NS( $offset );
	if( !$sub_r ) { return array( false, $offset ); }
	$namespace = $sub_r;

	list( $sub_r, $sub_offset ) = $this->xPN_LOCAL( $sub_offset );
	if( !$sub_r ) { return array( false, $offset ); }
	$local = $sub_r;

	return array( array( "type"=>"PNAME", "ns"=>$namespace, "local"=>$local ), $sub_offset );
}


#[164]	PN_CHARS_BASE	  ::=  	[A-Z] | [a-z] | [#x00C0-#x00D6] | [#x00D8-#x00F6] | [#x00F8-#x02FF] | [#x0370-#x037D] | [#x037F-#x1FFF] | [#x200C-#x200D] | [#x2070-#x218F] | [#x2C00-#x2FEF] | [#x3001-#xD7FF] | [#xF900-#xFDCF] | [#xFDF0-#xFFFD] | [#x10000-#xEFFFF]
function xPN_CHARS_BASE( $offset )
{
 	$char = $this->chars[$offset];
	if( $char >= "A" && $char <= "Z" ) { return array( $char, $offset+1 ); }
	if( $char >= "a" && $char <= "z" ) { return array( $char, $offset+1 ); }
	$u = $this->mb_ord( $char );
	if( $u >= 0x00C0 && $u <= 0x00D6 ) { return array( $char, $offset+1 ); }
	if( $u >= 0x00D8 && $u <= 0x00F6) { return array( $char, $offset+1 ); }
	if( $u >= 0x00F8 && $u <= 0x02FF) { return array( $char, $offset+1 ); }
	if( $u >= 0x0370 && $u <= 0x037D) { return array( $char, $offset+1 ); }
	if( $u >= 0x037F && $u <= 0x1FFF) { return array( $char, $offset+1 ); }
	if( $u >= 0x200C && $u <= 0x200D) { return array( $char, $offset+1 ); }
	if( $u >= 0x2070 && $u <= 0x218F) { return array( $char, $offset+1 ); }
	if( $u >= 0x2C00 && $u <= 0x2FEF) { return array( $char, $offset+1 ); }
	if( $u >= 0x3001 && $u <= 0xD7FF) { return array( $char, $offset+1 ); }
	if( $u >= 0xF900 && $u <= 0xFDCF) { return array( $char, $offset+1 ); }
	if( $u >= 0xFDF0 && $u <= 0xFFFD) { return array( $char, $offset+1 ); }
	if( $u >= 0x10000 && $u <= 0xEFFFF) { return array( $char, $offset+1 ); }
	
	return array( false, $offset );
}

#[165] 	PN_CHARS_U	  ::=  	PN_CHARS_BASE | '_'
function xPN_CHARS_U( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPN_CHARS_BASE( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

 	$char = $this->chars[$offset];
	if( $char == "_" ) { return array( "_", $offset+1 ); }

	return array( false, $offset );
}

#[167]	PN_CHARS	  ::=  	PN_CHARS_U | '-' | [0-9] | #x00B7 | [#x0300-#x036F] | [#x203F-#x2040]
function xPN_CHARS( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPN_CHARS_U( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

 	$char = $this->chars[$offset];
	if( $char == "-" ) { return array( $char, $offset+1 ); }
	if( $char >= "0" && $char <= "9" ) { return array( $char, $offset+1 ); }

	$u = $this->mb_ord( $char );
	if( $u == 0x00B7 ) { return array( $char, $offset+1 ); }
	if( $u >= 0x0300 && $u <= 0x036F) { return array( $char, $offset+1 ); }
	if( $u >= 0x203F && $u <= 0x2040) { return array( $char, $offset+1 ); }

	return array( false, $offset );
}

#[168]	PN_PREFIX	  ::=  	PN_CHARS_BASE ((PN_CHARS|'.')* PN_CHARS)?
function xPN_PREFIX( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPN_CHARS_BASE( $offset );
	if( !$sub_r ) { return array( false, $offset ); }

	$last_valid_match = $sub_r;
	$last_valid_offset = $sub_offset;
	$done = false;
	while( !$done )
	{
		if( $sub_offset >= sizeof( $this->chars ) ) { break; }
 		$char = $this->chars[$sub_offset];
		if( $char == "." ) 
		{
			$sub_r .= ".";
			$sub_offset++;
			continue;
		}

		if( (list( $sub_r2, $sub_offset ) = $this->xPN_CHARS( $sub_offset ) ) && $sub_r2 )
		{
			$sub_r .= $sub_r2;
			$last_valid_match = $sub_r;
			$last_valid_offset = $sub_offset;
		}
		else
		{
			$done = true;
		}
	}

	return array( $last_valid_match, $last_valid_offset );
	
}

#[169]	PN_LOCAL	  ::=  	(PN_CHARS_U | ':' | [0-9] | PLX ) ((PN_CHARS | '.' | ':' | PLX)* (PN_CHARS | ':' | PLX) )?
function xPN_LOCAL( $offset )
{
	# (PN_CHARS_U | ':' | [0-9] | PLX )

 	$char = $this->chars[$offset];
	if( (list( $sub_r, $sub_offset ) = $this->xPN_CHARS_U( $offset ) ) && $sub_r )
	{
		;
	}
	elseif( $char == ":" )
	{
		$sub_r = $char;
		$sub_offset = $offset + 1;
	}
	elseif( $char >= "0" && $char <= "9" )
	{
		$sub_r = $char;
		$sub_offset = $offset + 1;
	}
	elseif( (list( $sub_r, $sub_offset ) = $this->xPLX( $offset ) ) && $sub_r )
	{
		;
	}
	else
	{	
		return array( false, $offset );
	}


	# ok, get to the fiddley bit

	# ((PN_CHARS | '.' | ':' | PLX)* (PN_CHARS | ':' | PLX) )?
	# zero or more of these, and the last one can't be a "."1
	$last_valid_match = $sub_r;
	$last_valid_offset = $sub_offset;

	# keep trying to add ( PN_CHARS | '.' | ':' | PLX ) but only save results if 
	# it's not a "."
	$done = false;
	while( !$done )
	{
		if( $sub_offset >= sizeof( $this->chars ) ) { break; }
 		$char = $this->chars[$sub_offset];
		if( $char == "." ) 
		{
			$sub_r .= ".";
			$sub_offset++;
			continue;
		}

		if( (list( $sub_r2, $sub_offset ) = $this->xPN_CHARS( $sub_offset ) ) && $sub_r2 )
		{
			$sub_r .= $sub_r2;
		}
		elseif( $char == ":" )
		{
			$sub_r .= ":";
			$sub_offset = $offset + 1;
		}
		elseif( (list( $sub_r2, $sub_offset ) = $this->xPLX( $sub_offset ) ) && $sub_r2 )
		{
			$sub_r .= $sub_r2;
		}
		else
		{
			$done = true;
		}
	
		if( !$done )
		{			
			$last_valid_match = $sub_r;
			$last_valid_offset = $sub_offset;
		}
	}

	return array( $last_valid_match, $last_valid_offset );
}

#[170]	PLX		  ::=  	PERCENT | PN_LOCAL_ESC
function xPLX( $offset )
{
	list( $sub_r, $sub_offset ) = $this->xPERCENT( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

	list( $sub_r, $sub_offset ) = $this->xPN_LOCAL_ESC( $offset );
	if( $sub_r ) { return array( $sub_r, $sub_offset ); }

	return array( false, $offset );
}

#[171]	PERCENT	  ::=  	'%' HEX HEX
function xPERCENT( $offset )
{
	if( $this->chars[$offset] != "%" ) { return array( false, $offset ); }
	$sub_offset = $offset+1;
	
	list( $c1, $sub_offset ) = $this->xHEX( $sub_offset );
	if( !$c1 ) { return array( false, $offset ); }
	
	list( $c2, $sub_offset ) = $this->xHEX( $sub_offset );
	if( !$c2 ) { return array( false, $offset ); }

	# we don't decode this
	return array( "%".$c1.$c2, $sub_offset );
}
	

#[172]	HEX	  	::=  	[0-9] | [A-F] | [a-f]
function xHEX( $offset )
{
 	$char = $this->chars[$offset];

	if( $char >= "A" && $char <= "Z" ) { return array( $char, $offset+1 ); }
	if( $char >= "a" && $char <= "z" ) { return array( $char, $offset+1 ); }
	if( $char >= "0" && $char <= "9" ) { return array( $char, $offset+1 ); }

	return array( false, $offset ); 
}

#[173]	PN_LOCAL_ESC	  ::=  	'\' ( '_' | '~' | '.' | '-' | '!' | '$' | '&' | "'" | '(' | ')' | '*' | '+' | ',' | ';' | '=' | '/' | '?' | '#' | '@' | '%' )
function xPN_LOCAL_ESC( $offset )
{
	if( $this->chars[$offset] != "\\" ) { return array( false, $offset ); }

	$char = $this->chars[$offset+1];
	if( strpos( "_~.-!$&'()*+,;=/?#@%", $char ) === false ) { return array( false, $offset ); }

	return array( $char, $offset+2 );
}

function mb_ord( $char )
{
	list(, $ord) = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
	return $ord;	
}

function debug( $offset )
{
	$bt = debug_backtrace();
	print $bt[1]["function"]." (line ".$bt[0]["line"].")\n";
	print "OFFSET=$offset ";
	for( $i=$offset; $i<sizeof( $this->chars ) && $i<$offset+20; ++$i) { print $this->chars[$i]; }
	print "\n";

}

}

class PathException extends Exception {
	function __construct( $msg )
	{
	}
}
