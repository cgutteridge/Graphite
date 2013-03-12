<?php
include_once("arc/ARC2.php");
include_once("Graphite.php");
 
$graph = new Graphite();
$uri = "http://data.ordnancesurvey.co.uk/id/postcodeunit/SO171BJ";
$graph->load( $uri );
print $graph->resource( $uri )->dump();
//print $graph->resource( $uri )->dumpText();
?>
