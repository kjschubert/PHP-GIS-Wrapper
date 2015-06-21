<?php
require_once( dirname(__FILE__) . '/config.php');

class PeopleTest extends PHPUnit_Framework_TestCase
{

    private $_gis;

    protected function setUp()
    {
        $this->_gis = new \GIS\GIS(new \GIS\AuthProviderUser(API_USER, API_PASS));
    }

    public function testPersonId() {
        $this->_gis->reset();
        $this->_gis->people->filters->last_interaction = mktime (0, 0, 0, @date("n")-1, 20, @date("Y"));
        $max = $this->_gis->people->count();
        if($max > 100) $max = 100;
        $e = rand(0, $max);

        $i = 0;
        foreach($this->_gis->people as $p) {
            if($i == $max) {
                $this->assertEquals($p->email, $this->_gis->people->{'_' . $p->id}->get()->email);
                break;
            }
            $i++;
        }
    }

    /**
     * @expectedException GIS\InvalidAPIResponseException
     */
    public function testSuggestions() {
        $this->_gis->reset();
        $this->_gis->people->filters->last_interaction = mktime (0, 0, 0, @date("n")-1, 20, @date("Y"));
        $max = $this->_gis->people->count();
        if($max > 100) $max = 100;
        $e = rand(0, $max);

        $i = 0;
        foreach($this->_gis->people as $p) {
            if($i == $max) {
                $this->assertEquals(1, $this->_gis->people->{'_' . $p->id}->suggestions->count());
                break;
            }
            $i++;
        }
    }

    public function testComments() {
        $this->_gis->reset();
        $this->_gis->people->filters->last_interaction = mktime (0, 0, 0, @date("n")-1, 20, @date("Y"));
        $max = $this->_gis->people->count();
        if($max > 100) $max = 100;
        $e = rand(0, $max);

        $i = 0;
        foreach($this->_gis->people as $p) {
            if($i == $max) {
                foreach($this->_gis->people->{'_' . $p->id}->comments as $c) {
                    $this->assertEquals($c->comment, $this->_gis->people->{'_' . $p->id}->comments->{'_' . $c->id}->get()->comment);
                }
                break;
            }
            $i++;
        }
    }
}