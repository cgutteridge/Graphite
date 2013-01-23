<?php
include_once("arc/ARC2.php");
include_once("Graphite.php");
 
$graph = new Graphite();
$graph->load( "http://id.southampton.ac.uk/" );
print $graph->resource( "http://id.southampton.ac.uk/" )->get( "foaf:name" );
?>
