<?php

# These classes are only for internal use and their API may change
# without notice between versions. Use via $resouce->loadSPARQLPath(..)

class Graphite_SPARQLPathRefactor
{

var $ns;
var $max_depth;
var $include_prov;

function __construct( $ns=array(), $max_depth=8, $include_prov=false)
{
	$this->ns = $ns;
	$this->max_depth = $max_depth;
	$this->include_prov = $include_prov;
}



# * in a $to variable indicates a floating value, to 
# be assigned a new ?foo parameter any time it's used 
# ie. it's leaf end of the path, and not going to be 
# constrained by or linked to another triple.
function sparql( $tree, $from, $to="*", &$nextid=0, $top=true )
{
	# if the include_prov option is set, then we do a bit of
	# a shufty to add in some additional triples, but only in
	# the top level call to this function.
	if( $top && $this->include_prov )
	{
		list( $cons, $where ) = $this->sparql( $tree, $from, $to, $nextid,false );

		$time = date("c");
		$cons = "
<#provenanceEvent> a <http://www.w3.org/ns/prov#Activity> .
<#provenanceEvent> <http://www.w3.org/ns/prov#endedAtTime> \"$time\"^^<http://www.w3.org/2001/XMLSchema#dateTime> .
<#provenanceEvent> <http://www.w3.org/ns/prov#generated> <> .
<> <http://xmlns.com/foaf/0.1/primaryTopic> $from .
$cons
";
		return array( $cons, $where );
	}
	if( $tree["type"] == "seq" )
	{
		$ids = array();
		$ids[]=$from;
		for($i=0;$i<sizeof($tree["v"])-1;++$i)
		{
			$ids []= "?x".++$nextid;
		}
		$ids[]=$to;
		$cons = array();
		$where = array();
		$where []= "{";
		for($i=0;$i<sizeof($tree["v"]);++$i )
		{
			list( $cons2, $where2 ) = $this->sparql( $tree["v"][$i], $ids[$i], $ids[$i+1], $nextid,false );
			$cons[]=$cons2;
			$where[]=$where2;
		}
		$where []= "}";
		return array( join( "", $cons ), join( "", $where ) );
	}

	if( $tree["type"] == "alt" )
	{
		$cons = array();
		$where = array();
		foreach( $tree["v"] as $p )
		{
			list( $cons2, $where2 ) = $this->sparql( $p, $from, $to, $nextid,false );
			$cons[]=$cons2;
			$where[]=$where2;
		}
		return array( join( "", $cons ), "{ ".join( " } UNION { ", $where )." }" );
	}

	if( $tree["type"] == "IRIREF" )
	{
		$pred = "<".$tree["v"].">";
		if( $to == "*" ) { $to = "?x".++$nextid; }
		return $this->addGraphIfNeeded( array( "$from $pred $to . ", "$from $pred $to . " ), $nextid );
	}

	if( $tree["type"] == "inv" && $tree["v"]["type"] == "IRIREF" )
	{
		$pred = "<".$tree["v"]["v"].">";
		if( $to == "*" ) { $to = "?x".++$nextid; }
		return $this->addGraphIfNeeded( array( "$to $pred $from . ", "$to $pred $from . " ), $nextid );
	}
			
	if( $tree["type"] == "ANY" )
	{
		$pred = "?p".++$nextid;
		if( $to == "*" ) { $to = "?x".++$nextid; }
		return $this->addGraphIfNeeded( array( "$from $pred $to . ", "$from $pred $to . " ), $nextid );
	}

	if( $tree["type"] == "inv" && $tree["v"]["type"] == "ANY" )
	{
		$pred = "?p".++$nextid;
		if( $to == "*" ) { $to = "?x".++$nextid; }
		return $this->addGraphIfNeeded( array( "$to $pred $from . ", "$to $pred $from . " ), $nextid );
	}

	if( $tree["type"] == "NPS" )
	{
		# 3 cases
		# all normal paths
		# all inverted paths
		# mixture of paths	
		$fwd_paths = array();
		$inv_paths = array();
		foreach( $tree["v"] as $p )
		{
			if( $p["type"] == "inv" )
			{
				$inv_paths []= "<".$p["v"]["v"].">";
			}
			else
			{
				$fwd_paths []= "<".$p["v"].">";
			}
		}

		$cons = array();
		$where = array();

		if( sizeof( $fwd_paths ) )
		{
			$pred = "?p".++$nextid;
			if( $to == "*" ) { $to = "?x".++$nextid; }
			$pair = array( 
				"$from $pred $to . ",
				"$from $pred $to . FILTER ( $pred != ".join( " && $pred != ", $fwd_paths )." )" 
			);
			$this->addGraphIfNeeded( $pair, $nextid );
			$cons []= $pair[0];
			$where []= $pair[1];
		}
		
		if( sizeof( $inv_paths ) )
		{
			$pred = "?p".++$nextid;
			if( $to == "*" ) { $to = "?x".++$nextid; }
			$pair = array( 
				"$to $pred $from . ",
				"$to $pred $from . FILTER ( $pred != ".join( "&& $pred != ", $inv_paths )." )"
			);
			$this->addGraphIfNeeded( $pair, $nextid );
			$cons []= $pair[0];
			$where []= $pair[1];
		}

		return array( join( "", $cons ), "{ ".join( " } UNION { ", $where )." }" );
	}

	return array( "# error","# error, this line should not have been reached" );
}

function addGraphIfNeeded( $cons_where_pair, &$nextid )
{
	if( $this->include_prov )
	{
		list( $cons, $where ) = $cons_where_pair;
		$gr = "?g".++$nextid;
		$p = "?p".++$nextid;
		$o = "?o".++$nextid;
		$cons = "<#provenanceEvent> <http://www.w3.org/ns/prov#used> $gr . $gr $p $o .  $cons ";
		$where = "GRAPH $gr { $where } OPTIONAL { $gr $p $o } ";
		return array( $cons, $where );
	}

	return $cons_where_pair;
}

function render( $tree, $indent="" )
{
	if( $tree["type"] == "PNAME" )
	{	
		return $indent."pname(".$tree["ns"].":".$tree["local"].")\n";
	}
	if( $tree["type"] == "IRIREF" ) { return $indent."<".$tree["v"].">\n"; }
	if( $tree["type"] == "A" ) { return $indent."A\n"; }
	if( $tree["type"] == "ANY" ) { return $indent."ANY\n"; }
	if( $tree["type"] == "NULL" ) { return $indent."NULL\n"; }

	if( $tree["type"] == "NMPath" ) 
	{
		return $indent.$tree["type"]."{".$tree["n"]."..".$tree["m"]."}\n".$this->render($tree["v"],"$indent  "); 
	}

	$singleChild = false;
	if( $tree["type"] == "inv" ) { $singleChild = true; }
	if( $tree["type"] == "ZeroOrMorePath" ) { $singleChild = true; }
	if( $tree["type"] == "ZeroOrOnePath" ) { $singleChild = true; }
	if( $tree["type"] == "OneOrMorePath" ) { $singleChild = true; }
	if( $singleChild) { return $indent.$tree["type"]."\n".$this->render($tree["v"],"$indent  "); }

	$v = array();
	foreach( $tree["v"] as $p )
	{
		$v[]=$this->render( $p, "$indent  " );
	}
	return $indent.$tree["type"]."\n".join( "",$v);
}

#a/b*/c

#a/(NULL|b|b/b|b/b/b)/c
#a/c|a(b|b/b|b/b/b)/c

#A/NULL/B => A/B
#A|NULL|B (top level) A|B
#A/(B|NULL|C)/D = (A|D)|A/(B|C)/D
#simplify(A/(B|C|NULL)/(D|NULL)/E) => simplify(A/(B|C)/(D|NULL)/E)|simplify(A/(D|NULL)/E)


function multi( $tree, $min, $max )
{
	if( $min>$max ) { throw new Graphite_PathException( "min $min can't be greater than max $max" ); }
	$inner = $this->simplify( $tree["v"] );

	$v = array();
	for( $i=$min;$i<=$max;++$i )
	{
		$v2 = array();
		if( $i == 0 ) 
		{
			$v2 []= array( "type"=>"NULL" );
		}
		else
		{
			for( $j=0;$j<$i;++$j ) { $v2 []= $inner; }
		}
		$v []= array( "type"=>"seq", "v"=>$v2 );
	}
	$alt = array( "type"=>"alt", "v"=>$v );
	return $this->simplify( $alt );
}

function unionThenSequence($tree)
{
	if( $tree["type"] != "alt" && $tree["type"] != "seq" ) { return $tree; }

	$v=array();
	foreach( $tree["v"] as $child )
	{
		$v []= $this->unionThenSequence( $child );
	}
	$tree["v"] = $v;

	# remove any doubled up seq->seq or alt->alt pairs
	$tree = $this->simplify( $tree );

	if( $tree["type"] == "alt" ) { return $tree; }

	# start with an alt containing only one seq (the current tree)
	$alt_seqs = array( $tree ); 

	while(true)
	{
		# first test if any of the seqs in alt_seqs contain an alt
		# if none do then we're done.
		$seq_with_alt = false;
		$alt_pos = false;
		for( $i=0;$i<sizeof($alt_seqs);++$i)  
		{
			$alt_seq = $alt_seqs[$i];
			# we know this is of type seq so can loop over [v]
			for( $j=0;$j<sizeof( $alt_seq["v"] );++$j )
			{
				if( $alt_seq["v"][$j]["type"] == "alt" )
				{
					$seq_with_alt = $i;
					$alt_pos = $j;
					break( 2 );
				}
			}
		}
		if( $seq_with_alt === false ) { break; }

		# ok a sequence has an alt in!

		$list = array_splice( $alt_seqs, $seq_with_alt, 1 );		
		$seq_with_alt_in = array_pop($list);

		foreach( $seq_with_alt_in["v"][$alt_pos]["v"] as $alt_option )
		{
			$new_seq = array( "type"=>"seq", "v"=>array());
			for( $i=0; $i<sizeof($seq_with_alt_in["v"]); $i++ )
			{
				if( $i == $alt_pos )
				{
					$new_seq["v"][] = $alt_option;
				}
				else
				{
					$new_seq["v"][] = $seq_with_alt_in["v"][$i];
				}
			}
			$alt_seqs []= $new_seq;
		}
	}

	return array( "type"=>"alt", "v"=>$alt_seqs );
}

function simplify($tree)
{
	if( $tree["type"] == "ZeroOrMorePath" ) { return $this->multi( $tree, 0, $this->max_depth ); }
	if( $tree["type"] == "OneOrMorePath" ) { return $this->multi( $tree, 1, $this->max_depth ); }
	if( $tree["type"] == "ZeroOrOnePath" ) { return $this->multi( $tree, 0, 1 ); }
	if( $tree["type"] == "NMPath" ) 
	{ 
		$n = $tree["n"];
		if( $n == "") { $n = "0"; }
		$m = $tree["m"];
		if( $m == "") { $m = $this->max_depth; }
		return $this->multi( $tree, $n, $m ); 
	}

	if( $tree["type"] == "IRIREF" ) { return $tree; }
	if( $tree["type"] == "NULL" ) { return $tree; }
	if( $tree["type"] == "ANY" ) { return $tree; }
	if( $tree["type"] == "A" )
	{
		return array( "type"=>"IRIREF", "v"=>"http://www.w3.org/1999/02/22-rdf-syntax-ns#type" );
	}
	if( $tree["type"] == "PNAME" )
	{
		# look up ns later!! TODO
		if( !array_key_exists( $tree["ns"], $this->ns ) )
		{
			throw new Graphite_PathException( "namespace '".$tree["ns"]."' not defined" );
		}
		$uri = $this->ns[$tree["ns"]].$tree["local"];
		return array( "type"=>"IRIREF", "v"=>$uri );
	}

	# remove doubled up ^^
	if( $tree["type"] == "inv" && $tree["v"]["type"] == "inv" )
	{
		return $this->simplify( $tree["v"]["v"] ); 
	}

	if( $tree["type"] == "inv" )
	{
		$inv_v = $this->simplify( $tree["v"] );

		if( $inv_v["type"] == "NULL" )
		{
			return $inv_v;
		}

		# inv(NPS(x)) becomes NPS(inv(x))
		if( $inv_v["type"] == "NPS" )
		{
			$v=array();
			foreach( $inv_v["v"] as $p )
			{
				$v []= array( "type"=>"inv", "v"=>$p );
			}
			return $this->simplify( array( "type"=>"NPS", "v"=>$v ) );
		}
	
		# inv(alt(x,y)) becomes alt(inv(x),inv(y))
		if( $inv_v["type"] == "alt" )
		{
			$v=array();
			foreach( $inv_v["v"] as $p )
			{
				$v []= array( "type"=>"inv", "v"=>$p );
			}
			return $this->simplify( array( "type"=>"alt", "v"=>$v ) );
		}
	
		# inv(seq(x,y)) becomes seq(inv(y),inv(x))
		if( $inv_v["type"] == "seq" )
		{
			$v=array();
			foreach( $inv_v["v"] as $p )
			{
				$v []= array( "type"=>"inv", "v"=>$p );
			}
			return $this->simplify( array( "type"=>"seq", "v"=>array_reverse($v ) ) );
		}

		return array( "type"=>"inv", "v"=>$inv_v );
	}

	# process inside of an NPS	
	if( $tree["type"] == "NPS" )
	{
		$v=array();
		foreach( $tree["v"] as $p )
		{
			$v []= $this->simplify( $p );
		}
		return array( "type"=>"NPS", "v"=>$v );
	}
		
	if( $tree["type"] == "seq" || $tree["type"] == "alt" )
	{
		$v = array();
		foreach( $tree["v"] as $p )
		{
			$new_p = $this->simplify( $p );
			# if the simplified version of this property is now the same type as the
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

		# in a seq() look for any alt() which contain a NULL so
		# simplify( seq( A, alt( NULL,B ), C ) ) becomes simplify( alt( simplify( seq( A,C)), simplify( seq( A,B,C))))

		# if it's a seq,which contains an alt, which contains a NULL
		# split it in two on that alt
		if( $tree["type"]=="seq" )
		{
			$alt_off=0;
			foreach( $v as $p )
			{
				if( $p["type"] == "alt" )
				{
					foreach( $p["v"] as $p2 )
					{
						if( $p2["type"] == "NULL" )
						{
							return $this->denullseq( $v, $alt_off );
						}
					}	
				}
				$alt_off++;
			}
		}

		return array( "type"=>$tree["type"], "v"=>$v );
	}

	throw new Graphite_PathException( "unhandled structure: ".print_r( $tree, true) );
}

# turn a seq into two one with and one without a NULL
function denullseq( $seq_v, $n )
{
	$v1 = array();
	$v2 = array();
	$alt_off=0;
	foreach( $seq_v as $p )
	{
		if( $alt_off == $n )
		{
			# this is the alt with a NULL in
			$inner_v = array();
			foreach( $p["v"] as $p2 )
			{
				if( $p2["type"] != "NULL" ) { $inner_v []= $p2; }
			}
			$v2 []= array( "type"=>"alt", "v"=>$inner_v );	
		}	
		else
		{
			$v1[]=$p;
			$v2[]=$p;
		}
		$alt_off++;
	}	
	$v1_seq = $this->simplify( array( "type"=>"seq", "v"=>$v1 ) );
	$v2_seq = $this->simplify( array( "type"=>"seq", "v"=>$v2 ) );
	return $this->simplify( array( "type"=>"alt", "v"=>array( $v1_seq, $v2_seq ) ) );
}
		
}

class Graphite_ParserSPARQLPath {

# defaults
var $options = array( 
	"hyphen-inverse" => false,
	"wildcards" => false,
);

function __construct( $options = array() )
{
	foreach( $options as $k=>$v )
	{
		if( array_key_exists( $k, $this->options ) )
		{
			$this->options[ $k ] = $options[$k];
		}
		else
		{
			print "[[Unknown Graphite_ParserSPARQLPath option: $k]]\n";
		}
	}
}

var $chars = false;
function setString( $str )
{
	preg_match_all('/./u', $str, $results);
	$this->chars = $results[0];
}

function parseException( $expected, $offset )
{
	$msg = "Expected $expected at offset $offset: ";
	for( $i=0;$i<sizeof( $this->chars );++$i )
	{
		if( $i==$offset ) { $msg .= "<*>"; }
		$msg .= $this->chars[$i];
	}
	return new Graphite_PathException( $msg );
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
		if( !$sub_r ) { throw $this->parseException( "PathSequence", $offset ); }
		
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
		if( !$sub_r ) { throw $this->parseException( "PathEltOrInverse", $offset ); }
		
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

	list( $sub_r2, $sub_offset ) = $this->xPathNM( $sub_offset );
	if( $sub_r2 )
	{
		return array( array( "type"=>"NMPath", "n"=>$sub_r2["n"], "m"=>$sub_r2["m"], "v"=>$sub_r ), $sub_offset );
	}
	

	return array( $sub_r, $sub_offset );
}
	
# {m,n} syntax isn't in sparql 1.1 bui is handy and included
# in http://www.w3.org/TR/2010/WD-sparql11-property-paths-20100126/
# allowed forms: {n} {n,} {n,m} {,m}
function xPathNM( $offset )
{
	if( $offset >= sizeof( $this->chars ) ) { return array( false, $offset ); }

	$sub_offset = $offset;
 	$char = $this->chars[$sub_offset];
	if( $char != "{" ) { return array( false, $offset ); }

	$sub_offset++; # get past the {
 	$char = $this->chars[$sub_offset];
	$num1 = "";
	$num2 = "";
	while( $char != "," && $char != "}" )
	{
		# if we run out of characters before we hit a "}" or a ","
		# then this did not match
		if( $sub_offset >= sizeof( $this->chars ) ) { return array( false, $offset ); }

		if( $char<"0" || $char>"9" ) { throw $this->parseException( "0-9 in {}", $offset ); }

		$num1 += $char;
		$sub_offset++;
 		$char = $this->chars[$sub_offset];
	}

	# case for {n}
	if( $char == "}" ) 
	{
		return array( array( "n"=>$num1, "m"=>$num1 ), $sub_offset+1 );
	}

	# ok, must have been a ",", so lets do number2 (m)
		
	$sub_offset++; # get past the comma.
 	$char = $this->chars[$sub_offset];

	while( $char != "}" )
	{
		# if we run out of characters before we hit a "}"
		# then this did not match
		if( $sub_offset >= sizeof( $this->chars ) ) { throw $this->parseException( "'}' after '{'", $offset ); }

		if( $char<"0" || $char>"9" ) { throw $this->parseException( "0-9 in {}", $offset ); }

		$num2 += $char;
		$sub_offset++;
 		$char = $this->chars[$sub_offset];
	}

	# OK must have reached the "}"
	return array( array( "n"=>$num1, "m"=>$num2 ), $sub_offset+1 );
}

#[92]  	PathEltOrInverse	  ::=  	PathElt | '^' PathElt
function xPathEltOrInverse( $offset )
{
	$char = $this->chars[$offset];
	if( $char == "^" && !( $this->options["hyphen-inverse"] && $char=="-") ) 
	{
		$sub_offset = $offset+1;
		list( $sub_r, $sub_offset ) = $this->xPathElt( $sub_offset );
		if( !$sub_r ) { throw $this->parseException( "PathElt after '^'", $offset ); }
		
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
		if( !$sub_r ) { throw $this->parseException( "PathNegatedPropertySet after '!'", $offset ); }

		return array( $sub_r, $sub_offset );
	}
	
	# '(' Path ')'
	if( $char == "(" )
	{
		list( $sub_r, $sub_offset ) = $this->xPath( $offset+1 );
		if( !$sub_r ) { throw $this->parseException( "Path after '('", $offset ); }

		$char = $this->chars[$sub_offset];
		if( $char != ")" ) { throw $this->parseException( "')' after '('", $offset ); }
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
		if( $sub_r ) 
		{ 
			#NPS always returns a list, even if it's just one item
			return array( array( 
					"type"=>"NPS",
					"v"=>array( $sub_r ),
				      ), 
				      $sub_offset );
		}
		return array( false, $offset );
	}

	# '(' ( PathOneInPropertySet ( '|' PathOneInPropertySet )* )? ')'
	$sub_offset = $offset+1; # consume "("

	# out of stuff to parse?
	if( $sub_offset >= sizeof( $this->chars ) ) { throw $this->parseException( "')' after '('", $offset ); }

	$paths = array();

	while( true )
	{
		# Parse a pathone
		list( $sub_r, $sub_offset ) = $this->xPathOneInPropertySet( $sub_offset );
		if( !$sub_r ) { throw $this->parseException( "PathOneInPropertySet", $sub_offset ); }
		$paths []= $sub_r;	

		if( $sub_offset >= sizeof( $this->chars ) ) { throw $this->parseException( "')' after '('", $offset ); }
 		$char = $this->chars[$sub_offset];
		if( $char == ")" )
		{
			return array( array( "type"=>"NPS", "v"=>$paths ), $sub_offset+1 );
		}
	
		# if not a ")" then expect a "|" or fail
		if( $char == "|" )
		{
			$sub_offset++; # consume it
			continue;
		}
		throw $this->parseException( "')' or '|' in PathNegatedPropertySet", $offset ); 
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

	throw $this->parseException( "'a' or iri after '^'", $offset ); 
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
		if( $sub_offset >= sizeof( $this->chars ) ) { throw $this->parseException( "legal characters inside iri", $offset );  }

	 	$char = $this->chars[$sub_offset];

		# an IRI ref can't contiain these charaters
		if( strpos( "<\"{}|^`\\", $char ) !== false ) { throw $this->parseException( "legal characters inside iri", $offset );  }

		$u = $this->mb_ord( $char );
		if( $u <= 0x20 ) { throw $this->parseException( "legal characters inside iri", $offset );  }

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
	if( !$c1 ) { throw $this->parseException( "expected 2 hex digits after '%'", $offset );  } 
	
	list( $c2, $sub_offset ) = $this->xHEX( $sub_offset );
	if( !$c2 ) { throw $this->parseException( "expected 2 hex digits after '%'", $offset );  }

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
	if( strpos( "_~.-!$&'()*+,;=/?#@%", $char ) === false ) 
	{ 
		throw $this->parseException( "expected one of _~.-!$&'()*+,;=/?#@% after '\\'", $offset );  
	}

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

class Graphite_PathException extends Exception {
}

