#!/usr/bin/php
<?php

# this is intended to be run on the command-line, but you could run it
# via the web if you wanted.

# you'll need to change all the paths.

require_once("../arc/ARC2.php");
require_once("../Graphite.php");

$graph = new Graphite();
$graph->ns( "foo", "http://example.org/foons/" );
$graph->load( "mydata.rdf" );
$graph->freeze( "mydata.rdf.graphite" );

# to use this graph from a script, use
# $graph = Graphite::thaw( "mydata.rdf.graphite" );

