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
        $this->_cache = [
            'endpoint' => [
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => [],
                'params' => [
                    'arrayStrict' => [
                        'subparams' => [],
                        'operations' => [
                            'GET' => [
                                'type' => 'Array',
                                'required' => true
                            ]
                        ]
                    ],
                    'arrayStrictUnrequired' => [
                        'subparams' => [],
                        'operations' => [
                            'GET' => [
                                'type' => 'Array',
                                'required' => false
                            ]
                        ]
                    ],
                    'arrayStrictSub' => [
                        'subparams' => [
                            'param_1' => [
                                'subparams' => [],
                                'operations' => [
                                    'GET' => [
                                        'type' => 'String',
                                        'required' => true
                                    ]
                                ]
                            ],
                            'param_2' => [
                                'subparams' => [],
                                'operations' => [
                                    'GET' => [
                                        'type' => 'String',
                                        'required' => false
                                    ]
                                ]
                            ]
                        ],
                        'operations' => [
                            'GET' => [
                                'type' => 'Array',
                                'required' => false
                            ]
                        ]
                    ],
                    'mixed' => [
                        'subparams' => [],
                        'operations' => [
                            'POST' => [
                                'type' => 'Integer',
                                'required' => true
                            ],
                            'GET' => [
                                'type' => 'Array',
                                'required' => true
                            ]
                        ]
                    ],
                    'string' => [
                        'subparams' => [],
                        'operations' => [
                            'GET' => [
                                'type' => 'String',
                                'required' => false
                            ]
                        ]
                    ]
                ],
                'paged' => false,
                'operations' => ['GET']
            ],
            'magic' => [
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => [],
                'params' => [
                    'sub' => [
                        'subparams' => [
                            'a' => [
                                'subparams' => [],
                                'operations' => [
                                    'GET' => [
                                        'type' => 'Integer',
                                        'required' => false
                                    ]
                                ]
                            ],
                            'b' => [
                                'subparams' => [],
                                'operations' => [
                                    'GET' => [
                                        'type' => 'String',
                                        'required' => false
                                    ]
                                ]
                            ]
                        ],
                        'operations' => [
                            'GET' => [
                                'type' => 'Hash',
                                'required' => false
                            ]
                        ]
                    ],
                    'array' => [
                        'subparams' => [
                            'sub' => [
                                'subparams' => [],
                                'operations' => [
                                    'GET' => [
                                        'type' => 'String',
                                        'required' => false
                                    ]
                                ]
                            ]
                        ],
                        'operations' => [
                            'GET' => [
                                'type' => 'Array',
                                'required' => false
                            ]
                        ]
                    ]
                ],
                'paged' => false,
                'operations' => ['GET']
            ],
            'validation' => [
                'summary' => '',
                'path' => '',
                'endpoint' => true,
                'dynamic' => false,
                'dynamicSub' => false,
                'subs' => [],
                'params' => [
                    'int' => [
                        'subparams' => [],
                        'operations' => [
                            'GET' => [
                                'type' => 'Integer',
                                'required' => true
                            ]
                        ]
                    ],
                    'string' => [
                        'subparams' => [],
                        'operations' => [
                            'GET' => [
                                'type' => 'String',
                                'required' => true
                            ]
                        ]
                    ],
                    'date' => [
                        'subparams' => [],
                        'operations' => [
                            'GET' => [
                                'type' => 'Date',
                                'required' => true
                            ]
                        ]
                    ],
                    'bool' => [
                        'subparams' => [],
                        'operations' => [
                            'GET' => [
                                'type' => 'Virtus::Attribute::Boolean',
                                'required' => false
                            ]
                        ]
                    ],
                ],
                'paged' => false,
                'operations' => ['GET', 'POST']
            ]
        ];
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

        $this->assertCount(3, $this->_gis->endpoint->arrayStrictSub);
        $this->assertTrue(isset($this->_gis->endpoint->arrayStrictSub[1]));

        foreach($this->_gis->endpoint->arrayStrictSub as $el) {
            $this->assertEquals("Test Param 1", $el->param_1);
            $this->assertEquals("Test Param 2", $el->param_2);
        }

        unset($this->_gis->endpoint->arrayStrictSub[1]);

        $this->assertFalse(isset($this->_gis->endpoint->arrayStrictSub[1]));
    }

    public function testArrayStrictSubError() {
        // need to be implemented
    }

    public function testArrayValidation() {
        $this->assertFalse($this->_gis->endpoint->valid('GET'));
        $this->assertFalse($this->_gis->endpoint->arrayStrict->valid('GET'));

        $this->_gis->endpoint->arrayStrict = [0, 1, 2];

        $this->assertTrue($this->_gis->endpoint->arrayStrict->valid('GET'));

        $this->_gis->endpoint->arrayStrict = [];

        $this->assertFalse($this->_gis->endpoint->arrayStrict->valid('GET'));
        $this->assertTrue($this->_gis->endpoint->arrayStrictUnrequired->valid('GET'));
    }

    public function testMixed() {
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
        $this->_gis->endpoint->arrayStrict = [1, 2, 3];
        $this->_gis->endpoint->mixed = [1, 2, 3];
        
        $this->assertTrue($this->_gis->endpoint->valid('GET'));

        $this->_gis->endpoint->string = 12;

        $this->assertFalse($this->_gis->endpoint->valid('GET'));

        $this->_gis->endpoint->string = "12";

        $this->assertTrue($this->_gis->endpoint->valid('GET'));
    }

    public function testTypeValidation() {
        // int string date bool
        $this->assertFalse($this->_gis->validation->valid('GET'));

        $this->assertTrue($this->_gis->validation->valid('POST'));

        $this->_gis->validation->int = 0.00;
        $this->_gis->validation->string = 0.00;
        $this->_gis->validation->date = 0.00;
        $this->_gis->validation->bool = 0.00;

        $this->assertFalse($this->_gis->validation->valid('GET'));

        $this->_gis->validation->int = 10;
        $this->_gis->validation->string = "10";
        date_default_timezone_set('Europe/Berlin');
        $this->_gis->validation->date = new DateTime();
        $this->_gis->validation->bool = true;

        $this->assertTrue($this->_gis->validation->valid('GET'));
    }

    public function testMagicFunctions() {
        $this->assertTrue(($this->_gis->magic->array == "ArrayParameter"));

        $this->assertTrue($this->_gis->magic->sub->exists('a'));
        $this->assertFalse($this->_gis->magic->sub->exists('c'));

        $this->assertFalse(isset($this->_gis->magic->sub->a));
        $this->_gis->magic->sub->a = 10;
        $this->assertTrue(isset($this->_gis->magic->sub->a));
        $this->assertTrue(($this->_gis->magic->sub->a == "10"));

        unset($this->_gis->magic->sub->a);
        $this->assertFalse(isset($this->_gis->magic->sub->a));

        $this->_gis->magic->sub = ['a' => 10, 'b' => "20"];
        $this->assertEquals(10, $this->_gis->magic->sub->a);
        $this->assertEquals("20", $this->_gis->magic->sub->b);
    }
}