<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

class ParamsTest extends PHPUnit_Framework_TestCase
{
    private $_gis;
    private $_cache;
    private $_user;

    public function setUp()
    {
        $this->_cache = array(
            'endpoint' => array(
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => array(),
                'params' => array(
                    'arrayStrict' => array(
                        'subparams' => array(),
                        'operations' => array(
                            'GET' => array(
                                'type' => 'Array',
                                'required' => true
                            )
                        )
                    ),
                    'arrayStrictUnrequired' => array(
                        'subparams' => array(),
                        'operations' => array(
                            'GET' => array(
                                'type' => 'Array',
                                'required' => false
                            )
                        )
                    ),
                    'arrayStrictSub' => array(
                        'subparams' => array(
                            'param_1' => array(
                                'subparams' => array(),
                                'operations' => array(
                                    'GET' => array(
                                        'type' => 'String',
                                        'required' => true
                                    )
                                )
                            ),
                            'param_2' => array(
                                'subparams' => array(),
                                'operations' => array(
                                    'GET' => array(
                                        'type' => 'String',
                                        'required' => false
                                    )
                                )
                            )
                        ),
                        'operations' => array(
                            'GET' => array(
                                'type' => 'Array',
                                'required' => false
                            )
                        )
                    ),
                    'mixed' => array(
                        'subparams' => array(),
                        'operations' => array(
                            'POST' => array(
                                'type' => 'Integer',
                                'required' => true
                            ),
                            'GET' => array(
                                'type' => 'Array',
                                'required' => true
                            )
                        )
                    ),
                    'string' => array(
                        'subparams' => array(),
                        'operations' => array(
                            'GET' => array(
                                'type' => 'String',
                                'required' => false
                            )
                        )
                    )
                ),
                'paged' => false,
                'operations' => array('GET')
            )
        );
        $this->_user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $this->_gis = new \GISwrapper\GIS($this->_user, $this->_cache);
    }

    public function testKeys()
    {
        $this->assertNotNull($this->_gis->endpoint->arrayStrict);
        $this->assertNotNull($this->_gis->endpoint->arrayStrictSub);
        $this->assertNotNull($this->_gis->endpoint->arrayStrictSub[0]);
        $this->assertNotNull($this->_gis->endpoint->mixed);
        $this->assertNull($this->_gis->endpoint->string);
    }

    public function testInstances() {
        $this->assertInstanceOf('\GISwrapper\ParameterArrayType', $this->_gis->endpoint->arrayStrict);
        $this->assertInstanceOf('\GISwrapper\ParameterArrayType', $this->_gis->endpoint->arrayStrictSub);
        $this->assertInstanceOf('\GISwrapper\ParameterArrayType', $this->_gis->endpoint->mixed);
    }

    public function testArrayStrict() {
        $this->_gis->endpoint->arrayStrict[] = 13;
        $this->_gis->endpoint->arrayStrict[] = 14;
        $this->_gis->endpoint->arrayStrict[2] = 15;

        $this->assertEquals(13, $this->_gis->endpoint->arrayStrict[0]);
        $this->assertEquals(14, $this->_gis->endpoint->arrayStrict[1]);
        $this->assertEquals(15, $this->_gis->endpoint->arrayStrict[2]);

        foreach($this->_gis->endpoint->arrayStrict as $key => $val) {
            $this->assertGreaterThan(12, $val);
            $this->_gis->endpoint->arrayStrict[$key] = 12;
        }

        $this->assertEquals(12, $this->_gis->endpoint->arrayStrict[0]);
        $this->assertEquals(12, $this->_gis->endpoint->arrayStrict[1]);
        $this->assertEquals(12, $this->_gis->endpoint->arrayStrict[2]);
    }

    public function testArrayStrictError() {
        // needs to be implemented
    }

    public function testArrayStrictSub() {
        $this->assertInstanceOf('\GISwrapper\ParameterDefaultType', $this->_gis->endpoint->arrayStrictSub[0]);
        $this->assertInstanceOf('\GISwrapper\ParameterDefaultType', $this->_gis->endpoint->arrayStrictSub[1]);
        $this->assertInstanceOf('\GISwrapper\ParameterDefaultType', $this->_gis->endpoint->arrayStrictSub[2]);

        foreach($this->_gis->endpoint->arrayStrictSub as $key => $el) {
            $this->assertNull($el->param_1);
            $this->assertNull($el->param_2);
            $this->_gis->endpoint->arrayStrictSub[$key]->param_1 = "Test Param 1";
            $this->_gis->endpoint->arrayStrictSub[$key]->param_2 = "Test Param 2";
        }

        foreach($this->_gis->endpoint->arrayStrictSub as $el) {
            $this->assertEquals("Test Param 1", $el->param_1);
            $this->assertEquals("Test Param 2", $el->param_2);
        }
    }

    public function testArrayStrictSubError() {
        // need to be implemented
    }

    public function testArrayValidation() {
        unset($this->_gis->endpoint);
        $this->assertFalse($this->_gis->endpoint->valid('GET'));
        $this->assertFalse($this->_gis->endpoint->arrayStrict->valid('GET'));
        $this->_gis->endpoint->arrayStrict = [0, 1, 2];
        $this->assertTrue($this->_gis->endpoint->arrayStrict->valid('GET'));
        $this->_gis->endpoint->arrayStrict = [];
        $this->assertFalse($this->_gis->endpoint->arrayStrict->valid('GET'));
        $this->assertTrue($this->_gis->endpoint->arrayStrictUnrequired->valid('GET'));
    }

    public function testMixed() {
        unset($this->_gis->endpoint->mixed);
        $this->assertFalse($this->_gis->endpoint->mixed->valid('GET'));
        $this->assertFalse($this->_gis->endpoint->mixed->valid('POST'));
        $this->_gis->endpoint->mixed = 12;
        $this->assertFalse($this->_gis->endpoint->mixed->valid('GET'));
        $this->assertTrue($this->_gis->endpoint->mixed->valid('POST'));
        $this->_gis->endpoint->mixed = [1, 2, 3];
        $this->assertTrue($this->_gis->endpoint->mixed->valid('GET'));
        $this->assertTrue($this->_gis->endpoint->mixed->valid('POST'));
        $this->_gis->endpoint->mixed = 13;
        $this->assertTrue($this->_gis->endpoint->mixed->valid('GET'));
        $this->assertTrue($this->_gis->endpoint->mixed->valid('POST'));
    }

    public function testString() {
        unset($this->_gis->endpoint->arrayStrictSub);
        $this->_gis->endpoint->arrayStrict = [1, 2, 3];
        $this->_gis->endpoint->mixed = [1, 2, 3];
        
        $this->assertTrue($this->_gis->endpoint->valid('GET'));

        $this->_gis->endpoint->string = 12;

        $this->assertFalse($this->_gis->endpoint->valid('GET'));

        $this->_gis->endpoint->string = "12";

        $this->assertTrue($this->_gis->endpoint->valid('GET'));
    }
}