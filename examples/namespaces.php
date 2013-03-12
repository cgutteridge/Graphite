<?php
include_once("arc/ARC2.php");
include_once("Graphite.php");
 
$graph = new Graphite();
$graph->ns( "uosbuilding", "http://id.southampton.ac.uk/building/" );
$graph->load( "uosbuilding:32" );
print $graph->resource( "uosbuilding:32" )->label();
?>
