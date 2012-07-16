<?php
require_once 'Graphite.php';
require_once 'PHPUnit/Framework/TestCase.php';

class Graphite_DescriptionTest extends PHPUnit_Framework_TestCase {

    public function testAddRoute() {
        $this->markTestIncomplete();
    }

    public function testToDebug() {
        $this->markTestIncomplete();
    }

    public function test() {
        $this->markTestIncomplete(
            'function toJSON()
	        function _jsonify( $tree, $resource, &$json )
	        function toGraph()
	        function _tograph( $tree, $resource, &$new_graph )
	        function loadSPARQL( $endpoint, $debug = false )
	        function _toSPARQL($tree, $suffix, $in_dangler = null, $sparqlprefix = "", &$conbits )
            function getFormats()
	        function handleFormat( $format )'
        );
    }
}
