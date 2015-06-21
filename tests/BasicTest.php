<?php
require_once(dirname(__FILE__) . '/config.php');

class BasicTest extends PHPUnit_Framework_TestCase
{

    private $_gis;

    protected function setUp()
    {
        $this->_gis = new \GIS\GIS(new \GIS\AuthProviderUser(API_USER, API_PASS));
    }

    /**
     * @expectedException \GIS\InvalidAuthProviderException
     */
    public function testInvalidAuthProviderException() {
        $gis = new \GIS\GIS(new StdClass());
    }

    /**
     * @expectedException \GIS\InvalidAPIException
     */
    public function testInvalidAPIException() {
        $a = $this->_gis->DoesNotExist;
    }

    /**
     * @expectedException \GIS\InvalidCredentialsException
     */
    public function testInvalidCredentialsException() {
        $user = new \GIS\AuthProviderUser(API_USER, md5(API_PASS));
        $a = $user->getNewToken();
    }

    /**
     * @expectedException \GIS\InvalidChangeException
     */
    public function testAPIInvalidChangeException() {
        $this->_gis->reset();
        $a = $this->_gis->people->{'_' . $this->_gis->people->get()->id}->setInstance('00000');
    }

    /**
     * @expectedException \GIS\InvalidChangeException
     */
    public function testEndpointInvalidChangeException() {
        $this->_gis->reset();
        $a = $this->_gis->internal_comms->{'_' . $this->_gis->internal_comms->get()->id}->setInstance('00000');
    }

    public function testGetName() {
        foreach($this->_gis->getAPINames() as $name) {
            $this->assertEquals($name, $this->_gis->$name->getName());
        }
    }

    public function testAPIGarbageCollection() {
        $tmp = array();
        foreach($this->_gis->people as $p) {
            if(count($tmp) > 10) break;
            $tmp[($p->id)] = $this->_gis->people->{'_' . $p->id};
        }
        $this->_gis->people->garbageCollection(0);
        $keep = "";
        foreach($tmp as $id => $ep) {
            $this->assertFalse($ep === $this->_gis->people->{'_' . $id});
            $tmp[$id] = $this->_gis->people->{'_' . $id};
            $keep = $id;
        }
        $this->_gis->people->garbageCollection(0, $keep);
        $this->assertNotFalse($tmp[$keep] === $this->_gis->people->{'_' . $keep});
    }

    public function testEndpointGarbageCollection() {
        $tmp = array();
        foreach($this->_gis->internal_comms as $ic) {
            if(count($tmp) > 10) break;
            $tmp[$ic->id] = $this->_gis->internal_comms->{'_' . $ic->id};
        }
        $this->_gis->internal_comms->garbageCollection(0);
        $keep = 0;
        foreach($tmp as $id => $ic) {
            $this->assertFalse($ic === $this->_gis->internal_comms->{'_' . $id});
            $tmp[$id] = $this->_gis->internal_comms->{'_' . $id};
            $keep = $id;
        }
        $this->_gis->internal_comms->garbageCollection(0, $keep);
        $this->assertNotFalse($tmp[$keep] === $this->_gis->internal_comms->{'_' . $keep});
    }

    public function testGetNull() {
        $this->assertEquals(null, $this->_gis->search->DoesNotExists);
    }

    public function testReset() {
        $this->_gis->reset();
        $this->_gis->opportunities->q = "blabla";
        $this->assertEquals("blabla", $this->_gis->opportunities->q);
        $this->_gis->reset();
        $this->assertEquals("", $this->_gis->opportunities->q);
    }

    public function testDynamicReset() {
        $this->_gis->reset();

        $ids = array();
        foreach($this->_gis->people as $p) {
            if(count($ids) > 10) break;
            $this->_gis->people->{'_' . $p->id}->applications->filters->status = "matched";
            $this->assertEquals("matched", $this->_gis->people->{'_' . $p->id}->applications->filters->status);
            $ids[] = $p->id;
        }
        $this->_gis->reset();
        foreach($ids as $id) {
            $this->assertEquals("", $this->_gis->people->{'_' . $id}->applications->filters->status);
        }
    }

    public function testEndpointStuff() {
        // count
        $this->assertEquals($this->_gis->people->count(), $this->_gis->people->count);
        $this->assertGreaterThan(0, $this->_gis->people->count());

        // facets
        $this->assertEquals($this->_gis->opportunities->facets(), $this->_gis->opportunities->facets);
        $this->assertNotFalse(is_array($this->_gis->opportunities->facets()));
        $this->assertEquals(null, $this->_gis->internal_comms->facets());

        // getName
        $this->assertEquals("my", $this->_gis->opportunities->my->getName());

        // Parameters
        foreach($this->_gis->opportunities->getParams() as $n => $p) {
            if($p->hasChilds()) {
                $this->assertEquals($p->isRequired(), $this->_gis->opportunities->$n->isRequired());
                $this->assertEquals($p->getDescription(), $this->_gis->opportunities->$n->getDescription());

            }
        }
    }

    public function testEndpointParams() {
        $this->_gis->reset();
        $e = $this->_gis->people;
        $e->only = "facets";
        $e->filters->status = "completed";
        $e->filters->age->from = 18;
        $e->filters->programmes = array(1);
        $url = "";
        foreach($e->getParams() as $p) {
            $url .= $p->getURLString();
        }
        $this->assertNotFalse(strpos($url, 'only=facets'));
        $this->assertNotFalse(strpos($url, 'filters%5Bstatus%5D=completed'));
        $this->assertNotFalse(strpos($url, 'filters%5Bage%5D%5Bfrom%5D=18'));
        $this->assertNotFalse(strpos($url, 'filters%5Bprogrammes%5D%5B%5D=1'));
        $this->assertEquals(null, $this->_gis->opportunities->getParams()['q']->get());
    }
}