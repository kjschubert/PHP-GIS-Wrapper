<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

class APITest extends PHPUnit_Framework_TestCase
{
    private $_gis;
    private $_cache;
    private $_user;

    public function setUp() {
        $this->_cache = array(
            'api_endpoint' => array(
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => array(
                    'api' => array(
                        'endpoint' => false,
                        'dynamic' => false,
                        'dynamicSub' => false,
                        'subs' => array()
                    )
                ),
                'params' => array(
                    'some_id' => array(
                        'subparams' => array(),
                        'operations' => array(
                            'GET' => array(
                                'type' => 'Integer',
                                'required' => true
                            )
                        )
                    )
                ),
                'paged' => false,
                'operations' => array('GET')
            ),
            'api_endpoint_dynamicSub' => array(
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => true,
                'subs' => array(
                    '{dynamic}' => array(
                        'summary' => '',
                        'path' => __DIR__ . '/{dynamic}.json',
                        'endpoint' => true,
                        'dynamic' => true,
                        'dynamicSub' => false,
                        'subs' => array(),
                        'params' => array(),
                        'paged' => false,
                        'operations' => array('GET')
                    ),
                    'someSub' => array()
                ),
                'params' => array(),
                'paged' => false,
                'operations' => array('GET')
            ),
            'api_paged_endpoint' => array(
                'summary' => '',
                'path' => __DIR__ . '/paged.json',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => array(),
                'params' => array(),
                'paged' => true,
                'operations' => array('GET')
            ),
            'api_paged_endpoint_dynamicSub' => array(
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => true,
                'subs' => array(
                    '{dynamic}' => array(
                        'summary' => '',
                        'path' => __DIR__ . '/{dynamic}.json',
                        'endpoint' => true,
                        'dynamic' => true,
                        'dynamicSub' => false,
                        'subs' => array(),
                        'params' => array(),
                        'paged' => false,
                        'operations' => array('GET')
                    ),
                    'someSub' => array()
                ),
                'params' => array(),
                'paged' => true,
                'operations' => array('GET')
            ),
            'api' => array(
                'endpoint' => false,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => array(
                    'anotherAPI' => array(
                        'endpoint' => false,
                        'dynamic' => false,
                        'dynamicSub' => false,
                        'subs' => array()
                    )
                )
            )
        );
        $this->_user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $this->_gis = new \GISwrapper\GIS($this->_user, $this->_cache);
    }

    public function testGIS() {
        $this->assertEquals($this->_cache, $this->_gis->getCache());

        $this->assertTrue($this->_gis->exists('api'));
        $this->assertTrue($this->_gis->exists('api_endpoint'));
        $this->assertTrue($this->_gis->exists('api_endpoint_dynamicSub'));
        $this->assertTrue($this->_gis->exists('api_paged_endpoint'));
        $this->assertTrue($this->_gis->exists('api_paged_endpoint_dynamicSub'));
        $this->assertFalse($this->_gis->exists('unexistend_api'));

        $this->assertFalse(isset($this->_gis->api));
        $this->assertFalse(isset($this->_gis->api_endpoint));
        $this->assertFalse(isset($this->_gis->api_endpoint_dynamicSub));
        $this->assertFalse(isset($this->_gis->api_paged_endpoint));
        $this->assertFalse(isset($this->_gis->api_paged_endpoint_dynamicSub));

        $this->assertNotNull($this->_gis->api);
        $this->assertNotNull($this->_gis->api_endpoint);
        $this->assertNotNull($this->_gis->api_endpoint_dynamicSub);
        $this->assertNotNull($this->_gis->api_paged_endpoint);
        $this->assertNotNull($this->_gis->api_paged_endpoint_dynamicSub);

        $this->assertTrue(isset($this->_gis->api));
        $this->assertTrue(isset($this->_gis->api_endpoint));
        $this->assertTrue(isset($this->_gis->api_endpoint_dynamicSub));
        $this->assertTrue(isset($this->_gis->api_paged_endpoint));
        $this->assertTrue(isset($this->_gis->api_paged_endpoint_dynamicSub));
    }

    public function testInstanceClasses() {
        $this->assertInstanceOf('\GISwrapper\API', $this->_gis->api);
        $this->assertInstanceOf('\GISwrapper\APIEndpointPagedDynamicSub', $this->_gis->api_paged_endpoint_dynamicSub);
        $this->assertInstanceOf('\GISwrapper\APIEndpoint', $this->_gis->api_paged_endpoint_dynamicSub[0]);
        $this->assertInstanceOf('\GISwrapper\APIEndpointPaged', $this->_gis->api_paged_endpoint);
        $this->assertInstanceOf('\GISwrapper\APIEndpointDynamicSub', $this->_gis->api_endpoint_dynamicSub);
        $this->assertInstanceOf('\GISwrapper\APIEndpoint', $this->_gis->api_endpoint_dynamicSub[0]);
        $this->assertInstanceOf('\GISwrapper\APIEndpoint', $this->_gis->api_endpoint);
    }

    public function testAPI() {
        $this->assertTrue($this->_gis->api->exists('anotherAPI'));
        $this->assertFalse($this->_gis->api->exists('nonExistendAPI'));

        unset($this->_gis->api->anotherAPI);
        $this->assertFalse(isset($this->_gis->api->anotherAPI));
        $this->assertNotNull($this->_gis->api->anotherAPI);
        $this->assertTrue(isset($this->_gis->api->anotherAPI));
    }

    public function testEndpoint() {
        $this->assertFalse(isset($this->_gis->api_endpoint->api));
        $this->assertFalse(isset($this->_gis->api_endpoint->some_id));

        $this->assertTrue($this->_gis->api_endpoint->exists('api'));
        $this->assertTrue($this->_gis->api_endpoint->exists('some_id'));
        $this->assertFalse($this->_gis->api_endpoint->exists('unexistend_api'));
        $this->assertFalse($this->_gis->api_endpoint->exists('unexistend_param'));

        $this->assertNotNull($this->_gis->api_endpoint->api);
        $this->_gis->api_endpoint->some_id = 10;
        $this->assertEquals(10, $this->_gis->api_endpoint->some_id);

        $this->assertTrue(isset($this->_gis->api_endpoint->api));
        $this->assertTrue(isset($this->_gis->api_endpoint->some_id));

        unset($this->_gis->api_endpoint->api);
        unset($this->_gis->api_endpoint->some_id);

        $this->assertFalse(isset($this->_gis->api_endpoint->api));
        $this->assertFalse(isset($this->_gis->api_endpoint->some_id));

        $this->assertEquals(['GET'], $this->_gis->api_endpoint->getOperations());

        $this->assertFalse($this->_gis->api_endpoint->valid('GET'));
        $this->assertFalse($this->_gis->api_endpoint->valid('POST'));
        $this->_gis->api_endpoint->some_id = 12;
        $this->assertTrue($this->_gis->api_endpoint->valid('GET'));
        $this->assertFalse($this->_gis->api_endpoint->valid('POST'));
    }

    public function testEndpointDynamicSub() {
        // create testfile for dynamic Sub
        $filename = __DIR__ . '/12.json?access_token=' . $this->_user->getToken();
        file_put_contents($filename, '{"id": "12", "name": "Test"}');

        // test offsetExists
        $this->assertFalse(isset($this->_gis->api_endpoint_dynamicSub[12]));
        $this->assertFalse(isset($this->_gis->api_endpoint_dynamicSub[13]));
        $this->assertNotNull($this->_gis->api_endpoint_dynamicSub[12]);
        $this->assertNotNull($this->_gis->api_endpoint_dynamicSub[13]);
        $this->assertTrue(isset($this->_gis->api_endpoint_dynamicSub[12]));
        $this->assertTrue(isset($this->_gis->api_endpoint_dynamicSub[13]));

        // test exists
        $this->assertTrue($this->_gis->api_endpoint_dynamicSub->exists('someSub'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->exists('{dynamic}'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->exists('nonExistentSub'));
        $this->assertTrue($this->_gis->api_endpoint_dynamicSub->exists('12'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->exists('13'));

        // test existsSub
        $this->assertTrue($this->_gis->api_endpoint_dynamicSub->existsSub('someSub'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->existsSub('{dynamic}'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->existsSub('nonExistentSub'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->existsSub('12'));

        // test existsDynamicSub
        $this->assertTrue($this->_gis->api_endpoint_dynamicSub->existsDynamicSub('12'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->existsDynamicSub('13'));
        $this->assertFalse($this->_gis->api_endpoint_dynamicSub->existsDynamicSub('someSub'));

        // test offsetGet
        $el = $this->_gis->api_endpoint_dynamicSub[12];
        $this->assertNotNull($el);

        // test offsetSet
        $this->_gis->api_endpoint_dynamicSub[12] = $el;
        $this->assertEquals($el, $this->_gis->api_endpoint_dynamicSub[12]);

        // test offsetUnset
        unset($this->_gis->api_endpoint_dynamicSub[12]);
        $this->assertFalse(isset($this->api_endpoint_dynamicSub[12]));

        // delete test file
        unlink($filename);
    }

    public function testEndpointPaged() {
        // create assets
        $page = __DIR__ . '/paged.json?access_token=' . $this->_user->getToken();
        file_put_contents($page, file_get_contents(__DIR__ . '/assets/page1.json'));
        $page1 = __DIR__ . '/paged.json?page=1&access_token=' . $this->_user->getToken();
        file_put_contents($page1, file_get_contents(__DIR__ . '/assets/page1.json'));
        $page2 = __DIR__ . '/paged.json?page=2&access_token=' . $this->_user->getToken();
        file_put_contents($page2, file_get_contents(__DIR__ . '/assets/page2.json'));

        // check Iterator Interface
        $i = 1;
        foreach($this->_gis->api_paged_endpoint as $id => $el) {
            $this->assertEquals($i, $id);

            $this->assertObjectHasAttribute('id', $el);
            $this->assertObjectHasAttribute('name', $el);
            $this->assertEquals("element", substr($el->name, 0, 7));

            $this->assertEquals($id, $el->id);
            $i++;
        }
        $this->assertEquals(9, $i);

        // check Facets
        $facets = $this->_gis->api_paged_endpoint->getFacets();
        $this->assertNotNull($facets);
        $this->assertObjectHasAttribute("statusA", $facets);
        $this->assertObjectHasAttribute("statusB", $facets);
        $this->assertEquals(12, $facets->statusA);
        $this->assertEquals(24, $facets->statusB);

        // test count
        $this->assertEquals(8, $this->_gis->api_paged_endpoint->lastCount());
        $this->assertCount(8, $this->_gis->api_paged_endpoint);

        // delete temporary files
        unlink($page);
        unlink($page1);
        unlink($page2);
    }

    public function testEndpointPagedDynamicSub() {
        // create testfile for dynamic Sub
        $filename = __DIR__ . '/12.json?access_token=' . $this->_user->getToken();
        file_put_contents($filename, '{"id": "12", "name": "Test"}');

        // test offsetExists
        $this->assertFalse(isset($this->_gis->api_paged_endpoint_dynamicSub[12]));
        $this->assertFalse(isset($this->_gis->api_paged_endpoint_dynamicSub[13]));
        $this->assertNotNull($this->_gis->api_paged_endpoint_dynamicSub[12]);
        $this->assertNotNull($this->_gis->api_paged_endpoint_dynamicSub[13]);
        $this->assertTrue(isset($this->_gis->api_paged_endpoint_dynamicSub[12]));
        $this->assertTrue(isset($this->_gis->api_paged_endpoint_dynamicSub[13]));

        // test exists
        $this->assertTrue($this->_gis->api_paged_endpoint_dynamicSub->exists('someSub'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->exists('{dynamic}'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->exists('nonExistentSub'));
        $this->assertTrue($this->_gis->api_paged_endpoint_dynamicSub->exists('12'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->exists('13'));

        // test existsSub
        $this->assertTrue($this->_gis->api_paged_endpoint_dynamicSub->existsSub('someSub'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->existsSub('{dynamic}'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->existsSub('nonExistentSub'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->existsSub('12'));

        // test existsDynamicSub
        $this->assertTrue($this->_gis->api_paged_endpoint_dynamicSub->existsDynamicSub('12'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->existsDynamicSub('13'));
        $this->assertFalse($this->_gis->api_paged_endpoint_dynamicSub->existsDynamicSub('someSub'));

        // test offsetGet
        $el = $this->_gis->api_paged_endpoint_dynamicSub[12];
        $this->assertNotNull($el);

        // test offsetSet
        $this->_gis->api_paged_endpoint_dynamicSub[12] = $el;
        $this->assertEquals($el, $this->_gis->api_paged_endpoint_dynamicSub[12]);

        // test offsetUnset
        unset($this->_gis->api_paged_endpoint_dynamicSub[12]);
        $this->assertFalse(isset($this->api_paged_endpoint_dynamicSub[12]));

        // delete test file
        unlink($filename);
    }
}