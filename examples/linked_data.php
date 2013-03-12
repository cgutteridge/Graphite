<?php
include_once("arc/ARC2.php");
include_once("Graphite.php");
 
$person_uri = "http://eprints.ecs.soton.ac.uk/id/person/ext-1248";
 
$graph = new Graphite();
 
# this must be a directory the webserver can write to.
//$graph->cacheDir( "/usr/local/apache/sites/ecs.soton.ac.uk/graphite/htdocs/cache" );
$graph->cacheDir( "/tmp/" );
 
$graph->load( $person_uri );
 
$person = $graph->resource( $person_uri );
 
print "<h3>".$person->link()."</h3>";
 
# Show sameAs properties
foreach( $person->all( "owl:sameAs" ) as $sameas ) { print "<div>sameAs: ".$sameas->link()."</div>"; }
 
showPersonInfo("Before",$person);
 
# follow the sameAs links and load them into our graph
$person->loadSameAs();
 
showPersonInfo("After",$person);
 
function showPersonInfo($title,$person)
{
	print "<h4>$title</h4>";
	print "<div><b>name:</b> ".$person->all( "foaf:name" )->join( ", ")."</div>";
	print "<div><b>phone:</b> ".$person->all( "foaf:phone" )->prettyLink()->join( ", ")."</div>";
	print "<div><b>homepage:</b> ".$person->all( "foaf:homepage" )->link()->join( ", ")."</div>";
}
?>
