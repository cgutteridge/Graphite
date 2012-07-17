#!

# This script generates the "bundled" version of Graphite.php available from http://graphite.ecs.soton.ac.uk/download.php/Graphite.php

echo '<?php'
grep -h -v -e require_once -e '<?php' Graphite/Retriever.php Graphite.php Graphite/Node.php Graphite/Null.php Graphite/Resource.php Graphite/Relation.php Graphite/Description.php Graphite/InverseRelation.php Graphite/Literal.php Graphite/ResourceList.php
