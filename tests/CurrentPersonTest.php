<?php
require_once( dirname(__FILE__) . '/config.php');

class CurrentPersonTest extends PHPUnit_Framework_TestCase {

    private $_gis;

    protected function setUp() {
        $this->_gis = new \GIS\GIS(new \GIS\AuthProviderUser(API_USER, API_PASS));
    }

    public function testMail() {
        $this->_gis->reset();
        $this->assertEquals(API_USER, $this->_gis->current_person->get()->person->email);
    }

    public function testCount() {
        $this->_gis->reset();
        $this->assertEquals(1, $this->_gis->current_person->count());
    }

    public function testOffice() {
        $this->_gis->reset();
        $current_office = $this->_gis->current_person->get()->current_office;
        $oid = $current_office->id;
        $office = $this->_gis->offices->{'_' . $oid}->get();
        $this->assertEquals($current_office->name, $office->name);
        $this->assertEquals($current_office->full_name, $office->full_name);
    }

    public function testPosition() {
        $this->_gis->reset();
        $cp = $this->_gis->current_person->get();
        $position = $this->_gis->teams->{'_' . $cp->current_position->team->id}->positions->{'_' . $cp->current_position->id}->get();

        $this->assertEquals($cp->current_position->position_name, $position->position_name);
        $this->assertEquals($cp->current_position->position_short_name, $position->position_short_name);
        $this->assertEquals($cp->current_position->url, $position->url);
        $this->assertEquals($cp->current_position->start_date, $position->start_date);
        $this->assertEquals($cp->current_position->end_date, $position->end_date);
        $this->assertEquals($cp->current_position->job_description, $position->job_description);

        $this->assertEquals($cp->current_position->team->id, $position->team->id);

        $this->assertEquals($cp->person->id, $position->person->id);
        $this->assertEquals($cp->person->email, $position->person->email);
    }

    public function testTeams() {
        $this->_gis->reset();
        foreach($this->_gis->current_person->get()->current_teams as $team) {
            $t = $this->_gis->teams->{'_' . $team->id}->get();
            $this->assertEquals($team->id, $t->id);
            $this->assertEquals($team->title, $t->title);
        }
    }
}