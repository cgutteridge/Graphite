<?php
/**
 * A list to manage Graphite_Resources.
 *
 * To print a nicely formatted list of names, linking to the URIs.
 *
 * print $list->sort( "foaf:name" )->prettyLink()->join( ", ").".";
 *
 * * Note about Graphite methods which can take a list of resources
 *
 * These methods work in a pretty cool way. To make life easier for you they can take a list of resources in any of the following ways.
 *
 * $resource->get() is used as an example, it applies to many other methods.
 *
 * $resource->get( $uri_string )
 * Such as "http://xmlns.com/foaf/0.1/name".
 * $resource->get( $short_uri_string )
 * using any namespace defined with $graph->ns() or just built in. eg. "foaf:name".
 * $resource->get( $resource )
 * An instance of Graphite_resource.
 * $resource->get( $thing, $thing, $thing, ... )
 * $resource->get( array( $thing, $thing, $thing, ... ) )
 * Where each thing is any of $uri_string, $short_uri_string or $resource.
 * $resource->get( $resourcelist )
 * An instance of Graphite_resourceList.
 * This should make it quick and easy to write readable code!
 */
class Graphite_ResourceList extends ArrayIterator
{

	function __construct(Graphite $g, $a=array() )
	{
		$this->g = $g;
		$this->a = $a;
		if( $a instanceof Graphite_ResourceList )
		{
			print "<li>Graphite warning: passing a Graphite_ResourceList as the array passed to new Graphite_ResourceList will make weird stuff happen.</li>";
		}
		parent::__construct( $this->a );
	}

	/**
	 * Returns a string of all the items in the resource list, joined with the given string.
	 * $str = $resourcelist->join( $joinstr );
	 */
	function join( $str )
	{
		$first = 1;
		$l = array();
		foreach( $this as $resource )
		{
			if( !$first ) { $l []= $str; }
			$l []= Graphite::asString($resource);
			$first = 0;
		}
		return join( "", $l );
	}

	/**
	 * Returns a string containing a dump of all the resources in the list. Options is an optional array, same parameters as $options on a dump() of an individual resource. dumpText() does the same thing but with ASCII indents rather than HTML markup.
	 *
	 * $dump = $resourcelist->dump( [$options] );
	 */
	function dump()
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->dump();
		}
		return join( "", $l );
	}

	/**
	 * As dump() but for preformatted plain text, rather than HTML output.
	 *
	 * $dump = $resourcelist->dumpText( [$options] );
	 */
	function dumpText()
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->dumpText();
		}
		return join( "", $l );
	}

	/**
	 * Return a copy of this list.
	 *
	 * $new_resourcelist = $resourcelist->duplicate();
	 */
	public function duplicate()
	{
		$l = array();
		foreach( $this as $resource ) { $l []= $resource; }
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Return a copy of this resource list sorted by the given property or properties. If a resource has multiple values for a property then one will be used, as with $resource->get().
	 *
	 * $new_resourcelist = $resourcelist->sort( $property );
	 * $new_resourcelist = $resourcelist->sort( *resource list* );
	 */
	public function sort( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }


		/** @todo Remove global state */
		global $graphite_sort_args;
		$graphite_sort_args = array();
		foreach( $args as $arg )
		{
			if( $arg instanceof Graphite_Resource ) { $arg = Graphite::asString($arg); }
			$graphite_sort_args [] = $arg;
		}

		$l = array();
		foreach( $this as $resource ) { $l []= $resource; }
		usort($l, "graphite_sort_list_cmp" );
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Return a resource list with the same items but in a random order.
	 * 
	 * $resource_list = $resource->shuffle();
	 */
	function shuffle( $fn )
	{
		$l = array();
		foreach( $this as $resource ) { $l []= $resource; }
		shuffle( $l );
		return new Graphite_ResourceList($this->g,$l);
	}

	public function uasort( $cmp )
	{
		usort($this->a, $cmp );
	}

	/**
	 * Call $resource-&gt;get(...) on every item in this list and return a resourcelist of the results.
	 *
	 * $new_resourcelist = $resourcelist-&gt;get( $property );
	 * $new_resourcelist = $resourcelist-&gt;get( *resource list* );
	 */
	public function get( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->get( $args );
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Call $resource->getLiteral(...) on every item in this list and return a resourcelist of the results.
	 *
	 * $string = $resource->getLiteral( $property );
	 * $string = $resource->getLiteral( *resource list* );
	 */
	public function getLiteral( /* List */)
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->getLiteral( $args );
		}
		return new Graphite_ResourceList($this->g,$l);
	}
	/**
	 * @deprecated getString deprecated in favour of getLiteral
	 * @see getLiteral
	 */
	public function getString( /* List */ ) { return $this->getLiteral( func_get_args() ); }

	/**
	 * Call $resource->label() on every item in this list and return a resourcelist of the results.
	 *
	 * $new_resourcelist = $resourcelist->label();
	 */
	public function label()
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->label();
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Calls link() on each item in the resource list and returns it as an array. The array is an object which you can call join() on, so you can use:
	 *
	 * $array = $resourcelist->link();
	 */
	public function link()
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->link();
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Calls prettyLink() on each item in the resource list and returns it as an array. The array is an object which you can call join() on, so you can use:
	 *
	 * $array = $resourcelist->prettyLink();
	 */
	public function prettyLink()
	{
		$l = array();
		foreach( $this as $resource )
		{
			$l [] = $resource->prettyLink();
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Call $resource->load() on every item in this list and return the total triples from these resources. Careful, this could cause a large number of requests at one go!
	 *
	 * $n = $resourcelist->load();
	 */
	public function load()
	{
		$n = 0;
		foreach( $this as $resource )
		{
			$n += $resource->load();
		}
		return $n;
	}

	/**
	 * Call $resource->allString(...) on every item in this list and return a resourcelist of all the results. As with all(), duplicate resources and eliminated.
	 *
	 * $resource_list = $resource->allString( $property );
	 * $resource_list = $resource->allString( *resource list* );
	 */
	public function allString( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = array();
		$done = array();
		foreach( $this as $resource )
		{
			$all = $resource->all( $args );
			foreach( $all as $to_add )
			{
				if( isset($done[Graphite::asString($to_add)]) ) { continue; }
				$l []= Graphite::asString($to_add);
				$done[Graphite::asString($to_add)] = 1;
			}
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Call $resource->all(...) on every item in this list and return a resourcelist of all the results. Duplicate resources are eliminated.
	 *
	 * $new_resourcelist = $resourcelist->all( $property );
	 * $new_resourcelist = $resourcelist->all( *resource list* );
	 */
	public function all( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$l = array();
		$done = array();
		foreach( $this as $resource )
		{
			$all = $resource->all( $args );
			foreach( $all as $to_add )
			{
				if( isset($done[Graphite::asString($to_add)]) ) { continue; }
				$l []= $to_add;
				$done[Graphite::asString($to_add)] = 1;
			}
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Create a new resource list with the given resource or list of resources appended on the end of the current resource list.
	 *
	 * $new_resourcelist = $resourcelist->append( $resource );
	 * $new_resourcelist = $resourcelist->append( *resource list* );
	 */
	function append( $x /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

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
			if( isset( $done[Graphite::asString($resource)] ) ) { continue; }
			$l [] = $resource;
			$done[Graphite::asString($resource)]=1;
		}
		return new Graphite_ResourceList($this->g,$l);
	}

	/**
	 * Create a new resource list with the given resource or list of resources merged with the current list. Functionally the same as calling $resourcelist->append( ... )->distinct()
	 *
	 * $new_resourcelist = $resourcelist->union( $resource );
	 * $new_resourcelist = $resourcelist->union( *resource list* );
	 */
	function union( /* List */ )
	{
		$args = func_get_args();

		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$list = new Graphite_ResourceList($this->g);
		$done = array();
		foreach( $this as $resource )
		{
			if( isset( $done[Graphite::asString($resource)] ) ) { continue; }
			$list [] = $resource;
			$done[Graphite::asString($resource)]=1;
		}
		foreach( $args as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			if( isset( $done[Graphite::asString($arg)] ) ) { continue; }
			$list [] = $arg;
			$done[Graphite::asString($arg)]=1;
		}
		return $list;
	}

	/**
	 * Create a new resource list with containing only the resources which are in both lists. Only returns one instance of each resource no matter how many duplicates were in either list.
	 *
	 * $new_resourcelist = $resourcelist->intersection( $resource );
	 * $new_resourcelist = $resourcelist->intersection( *resource list* );
	 */
	function intersection( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$list = new Graphite_ResourceList($this->g,array());
		$seen = array();

		foreach( $this as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) {
				$arg = $this->g->resource( $arg );
			}
			$seen[Graphite::asString($arg)]=1;
		}

		foreach( $args as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) {
				$arg = $this->g->resource( $arg );
			}
			if( ! isset($seen[Graphite::asString($arg)]) ) {
				continue;
			}
			$list [] = $arg;
		}
		return $list;
	}

	/**
	 * Create a new resource list with containing only the resources which are in $resourcelist but not in the list being passed in. Only returns one instance of each resource no matter how many duplicates   were in either list.
	 *
	 * $new_resourcelist = $resourcelist->except( $resource );
	 * $new_resourcelist = $resourcelist->except( *resource list* );
	 */
	function except( /* List */ )
	{
		$args = func_get_args();
		if( isset($args[0]) && $args[0] instanceof Graphite_ResourceList ) { $args = $args[0]; }
		if( isset($args[0]) && is_array( $args[0] ) ) { $args = func_get_arg( 0 ); }

		$list = new Graphite_ResourceList($this->g,array());
		$exclude = array();

		foreach( $args as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			$exclude[Graphite::asString($arg)]=1;
		}
		foreach( $this as $arg )
		{
			if( ! $arg instanceof Graphite_Resource ) { $arg = $this->g->resource( $arg ); }
			if( isset($exclude[Graphite::asString($arg)]) ) { continue; }
			$list [] = $arg;
		}
		return $list;
	}

	/**
	 * Create a new resource list containing all resources in the current list of the given type.
	 *
	 * $resource_list = $resource->allOfType( $type_uri );
	 */
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

	/**
	 * Map a list of resources to a function which must return a resource or null 
	 * 
	 * $resource_list = $resource->map( function( $r ) { return $new_r; } );
	 */
	function map( $fn )
	{
		$list = new Graphite_ResourceList( $this->g, array() );
		foreach( $this as $item )
		{
			$new_item = $fn($item);
			if( $new_item !== null ) { $list [] = $new_item; }
		}
		return $list;
	}

	/**
	 * Filter a list of resources by calling a function on them and creating a new
	 * list of rseources for which the function returned true.
	 * 
	 * $resource_list = $resource->map( function( $r ) { return $bool; } );
	 */
	function filter( $fn )
	{
		$list = new Graphite_ResourceList( $this->g, array() );
		foreach( $this as $item )
		{
			if( $fn($item) )
			{
				$list [] = $new_item; 
			}
		}
		return $list;
	}



}
