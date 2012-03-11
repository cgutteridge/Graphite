<?php

require_once("../arc/ARC2.php");
require_once("../Graphite.php");

$graph = new Graphite();
$graph->load( "http://id.ecs.soton.ac.uk/person/1248" );
$me = $graph->resource( "http://id.ecs.soton.ac.uk/person/1248" );


print "<h1>".$me->prettyLink()."</h1>";
print "<p>default, built-in icons.</p>";
print "<div>".$me->get( "foaf:mbox" )->prettyLink()."</div>";
print "<div>".$me->get( "foaf:phone" )->prettyLink()."</div>";



print "<h1>".$me->prettyLink()."</h1>";
print '<p>The following icons by <a href="http://p.yusukekamiyamane.com/">Yusuke Kamiyamane</a>. All rights reserved. Licensed under a <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 License</a>.</p>';
$graph->mailtoIcon( "icons/mail.png" );
$graph->telIcon( "icons/telephone-handset.png" );
print "<div>".$me->get( "foaf:mbox" )->prettyLink()."</div>";
print "<div>".$me->get( "foaf:phone" )->prettyLink()."</div>";
