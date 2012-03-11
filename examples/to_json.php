<?php

require_once( "../arc/ARC2.php" );
require_once( "../Graphite.php" );

$graph = new Graphite();

$graph->ns( "sr", "http://data.ordnancesurvey.co.uk/ontology/spatialrelations/" );

$rd = $graph->resource( "http://id.southampton.ac.uk/building/32" )->prepareDescription();

$rd->addRoute( '*' );
$rd->addRoute( '*/rdfs:label' );
$rd->addRoute( '*/rdf:type' );
$rd->addRoute( '-sr:within/rdf:type' );
$rd->addRoute( '-sr:within/rdfs:label' );

$n = $rd->loadSPARQL( "http://sparql.data.southampton.ac.uk/" );

$rd->handleFormat( "json" );


