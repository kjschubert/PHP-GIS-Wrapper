<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

/**
 * Class SwaggerParserTest
 *
 * Testing the parsing of the swagger format
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 */
class SwaggerParserTest extends PHPUnit_Framework_TestCase
{
    private $_simpleCache;
    private $_fullCache;

    public function setUp() {
        $this->_simpleCache = \GISwrapper\GIS::generateSimpleCache(__DIR__ . '/assets/shortened/docs.json');
        $this->_fullCache = \GISwrapper\GIS::generateFullCache(__DIR__ . '/assets/shortened/docs.json');
    }

    public function testSimpleCache() {
        $this->assertCount(2, $this->_simpleCache, "Too many endpoints");
        $this->assertArraySubset(['applications' => './tests/assets/shortened/applications.json'], $this->_simpleCache);
        $this->assertArraySubset(['organisations' => './tests/assets/shortened/organisations.json'], $this->_simpleCache);
    }
    
    public function testFullCacheEndpoints() {
        // check that the right endpoints exist
        $this->assertCount(2, $this->_fullCache, "Too many endpoints");
        $this->assertArrayHasKey('applications', $this->_fullCache, "Endpoint applications missing");
        $this->assertArrayHasKey('organisations', $this->_fullCache, "Endpoint organisations missing");
    }

    public function testFullCacheApplicationsEndpoint() {
        $this->checkEndpointKeys($this->_fullCache['applications']);
        $this->assertEquals('', $this->_fullCache['applications']['summary'], 'Wrong summary');
        $this->assertEquals('https://gis-api.aiesec.org/v2/applications.json', $this->_fullCache['applications']['path'], 'wrong path');
        $this->assertTrue($this->_fullCache['applications']['endpoint'], "parameter endpoint not true");
        $this->assertFalse($this->_fullCache['applications']['dynamic'], "parameter dynamic not false");
        $this->assertFalse($this->_fullCache['applications']['dynamicSub'], "parameter dynamicSub not false");
        $this->assertTrue($this->_fullCache['applications']['paged'], "parameter paged not true");
        $this->assertArraySubset(['operations' => ['POST', 'GET']], $this->_fullCache['applications']);
    }

    public function testFullCacheApplicationsSubs() {
        $this->assertArrayHasKey('batch', $this->_fullCache['applications']['subs'], "Sub endpoint batch missing");
        $this->assertCount(1, $this->_fullCache['applications']['subs'], "too many subs");
        $this->checkEndpointKeys($this->_fullCache['applications']['subs']['batch'], "Sub endpoint batch invalid");
    }

    public function testFullCacheApplicationsParams() {
        $this->checkArrayKeys(['application', 'filters'], $this->_fullCache['applications']['params']);
        $this->assertCount(2, $this->_fullCache['applications']['params'], "Too many parameters");

        $this->checkParamKeys($this->_fullCache['applications']['params']['application']);
        $this->checkParamKeys($this->_fullCache['applications']['params']['filters']);

        // check some required settings
        $this->assertTrue($this->_fullCache['applications']['params']['application']['operations']['POST']['required'], "application parameter required not true");
        $this->assertTrue($this->_fullCache['applications']['params']['application']['subparams']['opportunity_id']['operations']['POST']['required'], "opportunity_id required parameter not true");
        $this->assertFalse($this->_fullCache['applications']['params']['application']['subparams']['person_id']['operations']['POST']['required'], "person_id required parameter not false");
    }

    public function testFullCacheOrganisationsEndpoint() {
        $this->checkEndpointKeys($this->_fullCache['organisations']);
        $this->assertEquals('', $this->_fullCache['organisations']['summary'], 'Wrong summary');
        $this->assertEquals('https://gis-api.aiesec.org/v2/organisations.json', $this->_fullCache['organisations']['path'], 'wrong path');
        $this->assertTrue($this->_fullCache['organisations']['endpoint'], "parameter endpoint not true");
        $this->assertFalse($this->_fullCache['organisations']['dynamic'], "parameter dynamic not false");
        $this->assertTrue($this->_fullCache['organisations']['dynamicSub'], "parameter dynamicSub not true");
        $this->assertFalse($this->_fullCache['organisations']['paged'], "parameter paged not false");
        $this->assertArraySubset(['operations' => ['POST', 'GET']], $this->_fullCache['organisations']);
    }

    public function testFullCacheOrganisationsSubs() {
        $this->assertCount(1, $this->_fullCache['organisations']['subs'], "too many subs");
        $this->assertArrayHasKey('{organisation_id}', $this->_fullCache['organisations']['subs'], "missing {organisation_id} sub endpoint");

        $this->checkAPIKeys($this->_fullCache['organisations']['subs']['{organisation_id}']);
        $this->assertTrue($this->_fullCache['organisations']['subs']['{organisation_id}']['dynamic'], "parameter dynamic not true");
        $this->assertFalse($this->_fullCache['organisations']['subs']['{organisation_id}']['dynamicSub'], "parameter dynamicSub not false");
    }

    private function checkParamKeys($param) {
        $this->checkArrayKeys(['subparams', 'operations'], $param);
    }

    private function checkEndpointKeys($endpoint) {
        $this->checkArrayKeys(['summary', 'path', 'endpoint', 'dynamic', 'dynamicSub', 'paged', 'operations', 'subs', 'params'], $endpoint);
    }

    private function checkAPIKeys($api) {
        $this->checkArrayKeys(['endpoint', 'dynamic', 'dynamicSub', 'subs'], $api);
    }

    private function checkArrayKeys($keys, $haystack) {
        foreach($keys as $key) {
            $this->assertArrayHasKey($key, $haystack, "Missing key $key");
        }
    }
}