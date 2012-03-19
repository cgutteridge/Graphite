<?php
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
