<?php
include_once("arc/ARC2.php");
include_once("Graphite.php");
 
$graph = new Graphite();
$graph->ns( "org", "http://www.w3.org/ns/org#" );
$uri = "http://id.southampton.ac.uk/org/F2";
$graph->load( $uri );
print $graph->resource( $uri )->all( "org:hasSubOrganization" )->sort( "rdfs:label" )->getString( "rdfs:label" )->join( ", " ).".\n";
?>