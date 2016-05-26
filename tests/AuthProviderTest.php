<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

class BasicTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \GISwrapper\InvalidAuthProviderException
     */
    public function testInvalidAuthProviderException() {
        $gis = new \GISwrapper\GIS(new StdClass());
    }

    /**
     * @expectedException \GISwrapper\InvalidCredentialsException
     */
    public function testInvalidCredentialsExceptionCombined() {
        $user = new \GISwrapper\AuthProviderCombined(EXPA_USER, md5(microtime()));
        $user->getToken();
    }

    /**
     * @expectedException \GISwrapper\InvalidCredentialsException
     */
    public function testInvalidCredentialsExceptionEXPA() {
        $user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, md5(microtime()));
        $user->getToken();
    }

    /**
     * @expectedException \GISwrapper\InvalidCredentialsException
     */
    public function testInvalidCredentialsExceptionOP() {
        $user = new \GISwrapper\AuthProviderOP(OP_USER, md5(microtime()));
        $user->getToken();
    }

    public function testAuthProviderEXPA() {
        $user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token doesn't match the format");

        // check expiration timestamp
        $this->assertNotNull($expires, "Expiration date for first token is null");
        $this->assertTrue(is_int($expires), "First token expiration date is not a timestamp");
        $this->assertGreaterThan(3540, $expires - time(), "First token expires too early");

        // sleep 2 seconds for expiration date
        sleep(2);

        // generate new token
        $token2 = $user->getNewToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token doesn't match the format");
        $this->assertNotEquals($token, $token2, "Both tokens are the same");

        // check timestamp
        $this->assertNotNull($user->getExpiresAt(), "Expiration date for second token is null");
        $this->assertTrue(is_int($user->getExpiresAt()), "Second token expiration date is not a timestamp");
        $this->assertGreaterThan($expires, $user->getExpiresAt(), "Both tokens have the same expiration date");

        // check that new token was saved
        $token = $user->getToken();
        $this->assertEquals($token, $token2, "New token wasn't saved");
    }

    public function testAuthProviderOP() {
        $user = new \GISwrapper\AuthProviderOP(OP_USER, OP_PW);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token doesn't match the format");

        // check expiration timestamp
        $this->assertNotNull($expires, "Expiration date for first token is null");
        $this->assertTrue(is_int($expires), "First token expiration date is not a timestamp");
        $this->assertGreaterThan(3540, $expires - time(), "First token expires too early");

        // sleep 2 seconds for expiration date
        sleep(2);

        // generate new token
        $token2 = $user->getNewToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token doesn't match the format");
        $this->assertNotEquals($token, $token2, "Both tokens are the same");

        // check timestamp
        $this->assertNotNull($user->getExpiresAt(), "Expiration date for second token is null");
        $this->assertTrue(is_int($user->getExpiresAt()), "Second token expiration date is not a timestamp");
        $this->assertGreaterThan($expires, $user->getExpiresAt(), "Both tokens have the same expiration date");

        // check that new token was saved
        $token = $user->getToken();
        $this->assertEquals($token, $token2, "New token wasn't saved");
    }

    public function testAuthProviderCombinedEXPA() {
        $user = new \GISwrapper\AuthProviderCombined(EXPA_USER, EXPA_PW);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token doesn't match the format");

        // check expiration timestamp
        $this->assertNotNull($expires, "Expiration date for first token is null");
        $this->assertTrue(is_int($expires), "First token expiration date is not a timestamp");
        $this->assertGreaterThan(3540, $expires - time(), "First token expires too early");

        // check type
        $this->assertTrue($user->isEXPA(), "isEXPA() didn't returned true for EXPA User");
        $this->assertFalse($user->isOP(), "isOP() didn't returned false for EXPA User");
        $this->assertEquals('EXPA', $user->getType(), "getType() didn't returned EXPA");

        // sleep 2 seconds for expiration date
        sleep(2);

        // generate new token
        $token2 = $user->getNewToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token doesn't match the format");
        $this->assertNotEquals($token, $token2, "Both tokens are the same");

        // check timestamp
        $this->assertNotNull($user->getExpiresAt(), "Expiration date for second token is null");
        $this->assertTrue(is_int($user->getExpiresAt()), "Second token expiration date is not a timestamp");
        $this->assertGreaterThan($expires, $user->getExpiresAt(), "Both tokens have the same expiration date");

        // check type
        $this->assertTrue($user->isEXPA(), "isEXPA() didn't returned true for EXPA User");
        $this->assertFalse($user->isOP(), "isOP() didn't returned false for EXPA User");
        $this->assertEquals('EXPA', $user->getType(), "getType() didn't returned EXPA");

        // check that new token was saved
        $token = $user->getToken();
        $this->assertEquals($token, $token2, "New token wasn't saved");

        // check current Person
        $this->assertObjectHasAttribute('person', $user->getCurrentPerson());
        $this->assertTrue(is_int($user->getCurrentPerson()->person->id));
        $this->assertNotCount(0, $user->getCurrentPerson()->current_positions);
    }

    public function testAuthProviderCombinedOP() {
        $user = new \GISwrapper\AuthProviderCombined(OP_USER, OP_PW);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token doesn't match the format");

        // check expiration timestamp
        $this->assertNotNull($expires, "Expiration date for first token is null");
        $this->assertTrue(is_int($expires), "First token expiration date is not a timestamp");
        $this->assertGreaterThan(3540, $expires - time(), "First token expires too early");

        // check type
        $this->assertFalse($user->isEXPA(), "isEXPA() didn't returned false for OP User");
        $this->assertTrue($user->isOP(), "isOP() didn't returned true for OP User");
        $this->assertEquals('OP', $user->getType(), "getType() didn't returned OP");

        // sleep 2 seconds for expiration date
        sleep(2);

        // generate new token
        $token2 = $user->getNewToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token doesn't match the format");
        $this->assertNotEquals($token, $token2, "Both tokens are the same");

        // check timestamp
        $this->assertNotNull($user->getExpiresAt(), "Expiration date for second token is null");
        $this->assertTrue(is_int($user->getExpiresAt()), "Second token expiration date is not a timestamp");
        $this->assertGreaterThan($expires, $user->getExpiresAt(), "Both tokens have the same expiration date");

        // check type
        $this->assertFalse($user->isEXPA(), "isEXPA() didn't returned false for OP User");
        $this->assertTrue($user->isOP(), "isOP() didn't returned true for OP User");
        $this->assertEquals('OP', $user->getType(), "getType() didn't returned OP");

        // check that new token was saved
        $token = $user->getToken();
        $this->assertEquals($token, $token2, "New token wasn't saved");

        // check current Person
        $this->assertObjectHasAttribute('person', $user->getCurrentPerson());
        $this->assertTrue(is_int($user->getCurrentPerson()->person->id));
        $this->assertCount(0, $user->getCurrentPerson()->current_positions);
    }

    public function testAuthProviderEXPAsession() {
        $session = './' . md5(microtime) . '.session';

        $user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $user->setSession($session);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token does not match format");

        // check getSession
        $this->assertEquals($session, $user->getSession(), "Session file path wasn't saved correctly");

        // check that session file exists
        $this->assertTrue(file_exists($session), "Session file does not exist");

        // sleep 2 seconds for expiration date
        sleep(2);

        // reuse session
        $user2 = new \GISwrapper\AuthProviderEXPA($session);
        $token2 = $user2->getToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token does not match format");
        $this->assertNotEquals($token, $token2, "Second token is not different from first one");

        // check expiration date
        $this->assertGreaterThan($expires, $user2->getExpiresAt(), "Both tokens have the same expiration date");

        // delete session
        unlink($session);
    }

    public function testAuthProviderOPsession() {
        $session = './' . md5(microtime) . '.session';

        $user = new \GISwrapper\AuthProviderOP(EXPA_USER, EXPA_PW);
        $user->setSession($session);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token does not match format");

        // check getSession
        $this->assertEquals($session, $user->getSession(), "Session file path wasn't saved correctly");

        // check that session file exists
        $this->assertTrue(file_exists($session), "Session file does not exist");

        // sleep 2 seconds for expiration date
        sleep(2);

        // reuse session
        $user2 = new \GISwrapper\AuthProviderOP($session);
        $token2 = $user2->getToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token does not match format");
        $this->assertNotEquals($token, $token2, "Second token is not different from first one");

        // check expiration date
        $this->assertGreaterThan($expires, $user2->getExpiresAt(), "Both tokens have the same expiration date");

        // delete session
        unlink($session);
    }

    public function testAuthProviderCombinedOPsession() {
        $session = './' . md5(microtime) . '.session';

        $user = new \GISwrapper\AuthProviderCombined(OP_USER, OP_PW);
        $user->setSession($session);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token does not match format");

        // check getSession
        $this->assertEquals($session, $user->getSession(), "Session file path wasn't saved correctly");

        // check that session file exists
        $this->assertTrue(file_exists($session), "Session file does not exist");

        // sleep 2 seconds for expiration date
        sleep(2);

        // reuse session
        $user2 = new \GISwrapper\AuthProviderCombined($session);
        $token2 = $user2->getToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token does not match format");
        $this->assertNotEquals($token, $token2, "Second token is not different from first one");

        // check type
        $this->assertTrue($user2->isOP(), "Token is not a OP token");

        // check expiration date
        $this->assertGreaterThan($expires, $user2->getExpiresAt(), "Both tokens have the same expiration date");

        // delete session
        unlink($session);
    }

    public function testAuthProviderCombinedEXPAsession() {
        $session = './' . md5(microtime) . '.session';

        $user = new \GISwrapper\AuthProviderCombined(EXPA_USER, EXPA_PW);
        $user->setSession($session);
        $token = $user->getToken();
        $expires = $user->getExpiresAt();

        // check token
        $this->assertStringMatchesFormat('%x', $token, "First token does not match format");

        // check getSession
        $this->assertEquals($session, $user->getSession(), "Session file path wasn't saved correctly");

        // check that session file exists
        $this->assertTrue(file_exists($session), "Session file does not exist");

        // sleep 2 seconds for expiration date
        sleep(2);

        // reuse session
        $user2 = new \GISwrapper\AuthProviderCombined($session);
        $token2 = $user2->getToken();

        // check token
        $this->assertStringMatchesFormat('%x', $token2, "Second token does not match format");
        $this->assertNotEquals($token, $token2, "Second token is not different from first one");

        // check type
        $this->assertTrue($user2->isEXPA(), "Token is not a EXPA token");

        // check expiration date
        $this->assertGreaterThan($expires, $user2->getExpiresAt(), "Both tokens have the same expiration date");

        // delete session
        unlink($session);
    }

    public function testAuthProviderEXPAinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token);

        unlink($session);
    }

    public function testAuthProviderOPinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderOP(OP_USER, OP_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token);

        unlink($session);
    }

    public function testAuthProviderCombinedEXPAinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderCombined(EXPA_USER, EXPA_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token);

        unlink($session);
    }

    public function testAuthProviderCombinedOPinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderCombined(OP_USER, OP_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token);

        unlink($session);
    }
}