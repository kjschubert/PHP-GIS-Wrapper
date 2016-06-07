<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

class APITest extends PHPUnit_Framework_TestCase
{
    private $_gis;
    private $_cache;
    private $_user;

    private $_files;

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
                        'path' => 'file://' . __DIR__ . '/{dynamic}.json',
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
                'path' => 'file://' . __DIR__ . '/paged.json',
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
                        'path' => 'file://' . __DIR__ . '/{dynamic}.json',
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
            ),
            'api_dynamicSub' => [
                'endpoint' => false,
                'dynamic' => false,
                'dynamicSub' => true,
                'subs' => array(
                    '{dynamic}' => array(
                        'summary' => '',
                        'path' => 'file://' . __DIR__ . '/{dynamic}.json',
                        'endpoint' => true,
                        'dynamic' => true,
                        'dynamicSub' => false,
                        'subs' => array(),
                        'params' => array(),
                        'paged' => false,
                        'operations' => array('GET')
                    ),
                    'someSub' => array()
                )
            ],
            'status' => array(
                'endpoint' => false,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => array(
                    'activeRole' => array(
                        'summary' => '',
                        'path' => 'file://' . __DIR__ . '/activeRole.json',
                        'endpoint' => true,
                        'dynamic' => false,
                        'dynamicSub' => false,
                        'subs' => array(),
                        'params' => array(),
                        'paged' => false,
                        'operations' => array('GET')
                    ),
                    'unauthorized' => array(
                        'summary' => '',
                        'path' => 'file://' . __DIR__ . '/unauthorized.json',
                        'endpoint' => true,
                        'dynamic' => false,
                        'dynamicSub' => false,
                        'subs' => array(),
                        'params' => array(),
                        'paged' => false,
                        'operations' => array('GET')
                    ),
                    'invalid' => array(
                        'summary' => '',
                        'path' => 'file://' . __DIR__ . '/invalid.json',
                        'endpoint' => true,
                        'dynamic' => false,
                        'dynamicSub' => false,
                        'subs' => array(),
                        'params' => array(),
                        'paged' => false,
                        'operations' => array('GET')
                    )
                )
            ),
            'noGet' => array(
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => array(),
                'params' => array(),
                'paged' => false,
                'operations' => array('POST')
            ),
        );
        $this->_user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $this->_gis = new \GISwrapper\GIS($this->_user, $this->_cache);

        $this->_files = array();

        // create testfile for dynamic Sub
        $file1 = __DIR__ . '/12.json';
        file_put_contents($file1, '{"id": "12", "name": "Test"}');
        $this->_files[] = $file1;

        // create testfiles for pagedEndpoint (can not simulate the page parameter on the filesystem)
        $file2 = __DIR__ . '/paged.json';
        file_put_contents($file2, file_get_contents(__DIR__ . '/assets/page1.json'));
        $this->_files[] = $file2;

        // create testfile for ActiveRoleException
        $file3 = __DIR__ . '/activeRole.json';
        file_put_contents($file3, '{"status": {"code": "403", "message": "Active role required to view this content."}}');
        $this->_files[] = $file3;

        // create testfile for InvalidAPIResponseExceptionException
        $file4 = __DIR__ . '/unauthorized.json';
        file_put_contents($file4, '{"status": {"code": "403", "message": "You are not authorized to perform the requested action"}}');
        $this->_files[] = $file4;

        // create testfile for ActiveRoleException
        $file5 = __DIR__ . '/invalid.json';
        file_put_contents($file5, '{"status": {"code": "500", "message": "Server Error"}}');
        $this->_files[] = $file5;
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

    public function testUnset() {
        $this->assertFalse(isset($this->_gis->api));
        $this->assertNotNull($this->_gis->api);
        $this->assertTrue(isset($this->_gis->api));
        unset($this->_gis->api);
        $this->assertFalse(isset($this->_gis->api));
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
    }

    public function testEndpointPaged() {
        // check Iterator Interface
        $i = 1;
        foreach($this->_gis->api_paged_endpoint as $id => $el) {
            $this->assertEquals($i, $id);

            $this->assertObjectHasAttribute('id', $el);
            $this->assertObjectHasAttribute('name', $el);
            $this->assertEquals("element", substr($el->name, 0, 7));

            $this->assertEquals($id, $el->id);

            // check that we are not in an infinite loop
            $this->assertLessThan(5, $i, "Loop running to long");

            // increment $i
            $i++;
        }
        $this->assertEquals(5, $i);

        // check Facets
        $facets = $this->_gis->api_paged_endpoint->getFacets();
        $this->assertNotNull($facets);
        $this->assertObjectHasAttribute("statusA", $facets);
        $this->assertObjectHasAttribute("statusB", $facets);
        $this->assertEquals(12, $facets->statusA);
        $this->assertEquals(24, $facets->statusB);

        // test count
        $this->assertEquals(4, $this->_gis->api_paged_endpoint->lastCount());
        $this->assertCount(4, $this->_gis->api_paged_endpoint);
    }

    public function testEndpointPagedDynamicSub() {
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
    }

    public function testAPIDynamicSub() {
        // test offsetExists
        $this->assertFalse(isset($this->_gis->api_dynamicSub[12]));
        $this->assertFalse(isset($this->_gis->api_dynamicSub[13]));
        $this->assertNotNull($this->_gis->api_dynamicSub[12]);
        $this->assertNotNull($this->_gis->api_dynamicSub[13]);
        $this->assertTrue(isset($this->_gis->api_dynamicSub[12]));
        $this->assertTrue(isset($this->_gis->api_dynamicSub[13]));

        // test exists
        $this->assertTrue($this->_gis->api_dynamicSub->exists('someSub'));
        $this->assertFalse($this->_gis->api_dynamicSub->exists('{dynamic}'));
        $this->assertFalse($this->_gis->api_dynamicSub->exists('nonExistentSub'));
        $this->assertTrue($this->_gis->api_dynamicSub->exists('12'));
        $this->assertFalse($this->_gis->api_dynamicSub->exists('13'));

        // test existsSub
        $this->assertTrue($this->_gis->api_dynamicSub->existsSub('someSub'));
        $this->assertFalse($this->_gis->api_dynamicSub->existsSub('{dynamic}'));
        $this->assertFalse($this->_gis->api_dynamicSub->existsSub('nonExistentSub'));
        $this->assertFalse($this->_gis->api_dynamicSub->existsSub('12'));

        // test existsDynamicSub
        $this->assertTrue($this->_gis->api_dynamicSub->existsDynamicSub('12'));
        $this->assertFalse($this->_gis->api_dynamicSub->existsDynamicSub('13'));
        $this->assertFalse($this->_gis->api_dynamicSub->existsDynamicSub('someSub'));

        // test offsetGet
        $el = $this->_gis->api_dynamicSub[12];
        $this->assertNotNull($el);

        // test offsetSet
        $this->_gis->api_dynamicSub[12] = $el;
        $this->assertEquals($el, $this->_gis->api_dynamicSub[12]);

        // test offsetUnset
        unset($this->_gis->api_dynamicSub[12]);
        $this->assertFalse(isset($this->api_dynamicSub[12]));
    }

    /**
     * @expectedException \GISwrapper\ParameterRequiredException
     */
    public function testParameterRequiredException() {
        $this->_gis->api_endpoint->get();
    }

    /**
     * @expectedException \GISwrapper\OperationNotAvailableException
     * @expectedExceptionMessage Operation GET is not available
     */
    public function testOperationNotAvailableExceptionGET() {
        $this->_gis->noGet->get();
    }

    /**
     * @expectedException \GISwrapper\OperationNotAvailableException
     * @expectedExceptionMessage Operation PATCH is not available
     */
    public function testOperationNotAvailableExceptionPATCH() {
        $this->_gis->api_endpoint->update();
    }

    /**
     * @expectedException \GISwrapper\OperationNotAvailableException
     * @expectedExceptionMessage Operation POST is not available
     */
    public function testOperationNotAvailableExceptionPOST() {
        $this->_gis->api_endpoint->create();
    }

    /**
     * @expectedException \GISwrapper\OperationNotAvailableException
     * @expectedExceptionMessage Operation DELETE is not available
     */
    public function testOperationNotAvailableExceptionDELETE() {
        $this->_gis->api_endpoint->delete();
    }

    /**
     * @expectedException \GISwrapper\ActiveRoleRequiredException
     */
    public function testStatusActiveRoleRequired() {
        $this->_gis->status->activeRole->get();
    }

    /**
     * @expectedException \GISwrapper\UnauthorizedException
     */
    public function testStatusUnauthorized() {
        $this->_gis->status->unauthorized->get();
    }

    /**
     * @expectedException \GISwrapper\InvalidAPIResponseException
     * @expectedExceptionMessage Server Error
     */
    public function testInvalidResponse() {
        $this->_gis->status->invalid->get();
    }

    public function tearDown() {
        foreach($this->_files as $file) {
            unlink($file);
        }
    }
}