<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

class OnlineTest extends PHPUnit_Framework_TestCase
{

    private $_user;
    private $_gis;

    public function setUp() {
        $this->_user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $this->_gis = new \GISwrapper\GIS($this->_user);
    }

    public function testGetUid() {
        $uid = $this->_gis->current_person->get()->person->id;
        $this->assertNotNull($uid);

        return $uid;
    }

    public function testOpportunityCreate() {
        $title = "Opportunity to test PHP-GIS-Wrapper";
        $pid = 1;

        $this->_gis->opportunities = ['opportunity' => ['title' => $title, 'programme_id' => $pid]];
        $this->assertEquals($title, $this->_gis->opportunities->opportunity->title);

        unset($this->_gis->opportunities);
        $this->_gis->opportunities->opportunity = ['title' => $title, 'programme_id' => $pid];
        $this->assertEquals($title, $this->_gis->opportunities->opportunity->title);

        unset($this->_gis->opportunities);
        $this->_gis->opportunities->opportunity->title = $title;
        $this->_gis->opportunities->opportunity->programme_id = $pid;
        $this->_gis->opportunities->opportunity->branch_id = "325363";
        $this->_gis->opportunities->opportunity->host_lc_id = "257";
        $this->assertEquals($title, $this->_gis->opportunities->opportunity->title);

        $res = $this->_gis->opportunities->create();
        $this->assertEquals($title, $res->title);

        return $res->id;
    }

    /**
     * @depends testGetUid
     * @depends testOpportunityCreate
     */
    public function testAddManager($uid, $oid) {
        $this->_gis->opportunities[$oid]->opportunity->manager_ids[] = $uid;
        $this->assertNotNull($this->_gis->opportunities[$oid]->opportunity->manager_ids[0]);
        $this->_gis->opportunities[$oid]->update();

        $this->assertEquals($uid, $this->_gis->opportunities[$oid]->get()->managers[0]->id);

        return $oid;
    }

    /**
     * @depends testAddManager
     */
    public function testDelete($oid) {
        $this->_gis->opportunities[$oid]->delete();
        $this->assertFalse($this->_gis->opportunities->existsDynamicSub($oid));
    }

    public function testGetRequestWithParams() {
        $this->_gis->opportunities = [
            'filters' => [
                'languages' => [
                    [
                        'id' => 24,
                        'option' => 'required'
                    ],
                ],
                'programmes' => [1, 2]
            ]
        ];

        $this->assertEquals(24, $this->_gis->opportunities->filters->languages[0]->id);
        $this->assertEquals('required', $this->_gis->opportunities->filters->languages[0]->option);
        $this->assertEquals(1, $this->_gis->opportunities->filters->programmes[0]);
        $this->assertEquals(2, $this->_gis->opportunities->filters->programmes[1]);

        $i = 0;
        foreach($this->_gis->opportunities as $o) {
            // limit to 10 opportunities
            if($i >= 10) break;
            $i++;

            // check that programme filter works
            $this->assertTrue(in_array($o->programmes->id, [1, 2]));

            // get full opportunity to check for language
            $o = $this->_gis->opportunities[$o->id]->get();
            $languages = array();
            foreach($o->languages as $l) {
                $languages[$l->id] = $l->option;
            }
            $this->assertArrayHasKey(24, $languages);

            // unset opportunity
            unset($this->_gis->opportunities[$o->id]);
        }
    }
}