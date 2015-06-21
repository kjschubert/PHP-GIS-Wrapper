<?php
require_once( dirname(__FILE__) . '/config.php');

class CompaniesTest extends PHPUnit_Framework_TestCase
{

    private $_gis;

    protected function setUp()
    {
        $this->_gis = new \GIS\GIS(new \GIS\AuthProviderUser(API_USER, API_PASS));
    }

    /**
     * @dataProvider companieProvider
     */
    public function testCompanyAndBranches($cid, $cname) {
        $this->_gis->reset();
        $companie = $this->_gis->companies->{'_' . $cid}->get();

        // check companie name
        $this->assertEquals($cname, $companie->name);

        // check number of branches
        $this->assertEquals(count($companie->branches), $this->_gis->companies->{'_' . $cid}->branches->count());

        // save ids and names of branches
        $v = array();
        foreach($companie->branches as $b) {
            $v[$b->id] = $b->name;
        }

        // check ids and names of branches
        foreach($this->_gis->companies->{'_' . $cid}->branches as $b) {
            $this->assertEquals($b->name, $v[$b->id]);
        }
    }

    /**
     * @dataProvider companieProvider
     */
    public function testCompanieAndComments($cid, $cname) {
        $this->_gis->reset();
        $companie = $this->_gis->companies->{'_' . $cid}->get();

        // check companie name
        $this->assertEquals($cname, $companie->name);

        // check comments
        foreach($this->_gis->companies->{'_' . $cid}->comments as $c) {
            $d = $this->_gis->companies->{'_' . $cid}->comments->{'_' . $c->id}->get();
            $this->assertEquals($c->id, $d->id);
            $this->assertEquals($c->comment, $d->comment);
        }
    }

    public function companieProvider() {
        $gis = new \GIS\GIS(new \GIS\AuthProviderUser(API_USER, API_PASS));
        $r = array(array(323146, "Bolyai Gyermekotthon"));
        $i = 0;
        foreach($gis->companies as $c) {
            if($i > 10) break;
            $r[] = array($c->id, $c->name);
            $i++;
        }
        return $r;
    }
}