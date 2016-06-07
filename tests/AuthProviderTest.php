<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

/**
 * Class AuthProviderTest
 *
 * Testing the AuthProviders
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 */
class AuthProviderTest extends PHPUnit_Framework_TestCase
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

    /**
     * @covers \GISwrapper\AuthProviderEXPA::__construct
     * @covers \GISwrapper\AuthProviderEXPA::getToken
     * @covers \GISwrapper\AuthProviderEXPA::getNewToken
     * @covers \GISwrapper\AuthProviderEXPA::getExpiresAt
     * @covers \GISwrapper\AuthProviderEXPA::generateNewToken
     */
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

    /**
     * @covers \GISwrapper\AuthProviderOP::__construct
     * @covers \GISwrapper\AuthProviderOP::getToken
     * @covers \GISwrapper\AuthProviderOP::getNewToken
     * @covers \GISwrapper\AuthProviderOP::getExpiresAt
     * @covers \GISwrapper\AuthProviderOP::generateNewToken
     * @covers \GISwrapper\AuthProviderOP::proceedToken
     */
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

    /**
     * @covers \GISwrapper\AuthProviderCombined::__construct
     * @covers \GISwrapper\AuthProviderCombined::getToken
     * @covers \GISwrapper\AuthProviderCombined::getNewToken
     * @covers \GISwrapper\AuthProviderCombined::getExpiresAt
     * @covers \GISwrapper\AuthProviderCombined::generateNewToken
     * @covers \GISwrapper\AuthProviderCombined::isEXPA
     * @covers \GISwrapper\AuthProviderCombined::isOP
     * @covers \GISwrapper\AuthProviderCombined::getType
     * @covers \GISwrapper\AuthProviderCombined::getCurrentPerson
     */
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

    /**
     * @covers \GISwrapper\AuthProviderCombined::__construct
     * @covers \GISwrapper\AuthProviderCombined::getToken
     * @covers \GISwrapper\AuthProviderCombined::getNewToken
     * @covers \GISwrapper\AuthProviderCombined::getExpiresAt
     * @covers \GISwrapper\AuthProviderCombined::generateNewToken
     * @covers \GISwrapper\AuthProviderCombined::isEXPA
     * @covers \GISwrapper\AuthProviderCombined::isOP
     * @covers \GISwrapper\AuthProviderCombined::getType
     * @covers \GISwrapper\AuthProviderCombined::getCurrentPerson
     */
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

    /**
     * @covers \GISwrapper\AuthProviderEXPA::__construct
     * @covers \GISwrapper\AuthProviderEXPA::getToken
     * @covers \GISwrapper\AuthProviderEXPA::getExpiresAt
     * @covers \GISwrapper\AuthProviderEXPA::generateNewToken
     * @covers \GISwrapper\AuthProviderEXPA::getSession
     * @covers \GISwrapper\AuthProviderEXPA::setSession
     */
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

    /**
     * @covers \GISwrapper\AuthProviderOP::__construct
     * @covers \GISwrapper\AuthProviderOP::getToken
     * @covers \GISwrapper\AuthProviderOP::getExpiresAt
     * @covers \GISwrapper\AuthProviderOP::generateNewToken
     * @covers \GISwrapper\AuthProviderOP::proceedToken
     * @covers \GISwrapper\AuthProviderOP::getSession
     * @covers \GISwrapper\AuthProviderOP::setSession
     */
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

    /**
     * @covers \GISwrapper\AuthProviderCombined::__construct
     * @covers \GISwrapper\AuthProviderCombined::getToken
     * @covers \GISwrapper\AuthProviderCombined::getExpiresAt
     * @covers \GISwrapper\AuthProviderCombined::generateNewToken
     * @covers \GISwrapper\AuthProviderCombined::getSession
     * @covers \GISwrapper\AuthProviderCombined::setSession
     * @covers \GISwrapper\AuthProviderCombined::isOP
     */
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

    /**
     * @covers \GISwrapper\AuthProviderCombined::__construct
     * @covers \GISwrapper\AuthProviderCombined::getToken
     * @covers \GISwrapper\AuthProviderCombined::getExpiresAt
     * @covers \GISwrapper\AuthProviderCombined::generateNewToken
     * @covers \GISwrapper\AuthProviderCombined::getSession
     * @covers \GISwrapper\AuthProviderCombined::setSession
     * @covers \GISwrapper\AuthProviderCombined::isEXPA
     */
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

    /**
     * @covers \GISwrapper\AuthProviderEXPA::__construct
     * @covers \GISwrapper\AuthProviderEXPA::getToken
     * @covers \GISwrapper\AuthProviderEXPA::generateNewToken
     * @covers \GISwrapper\AuthProviderEXPA::setSession
     */
    public function testAuthProviderEXPAinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token, "Token doesn't match format");

        unlink($session);
    }

    /**
     * @covers \GISwrapper\AuthProviderOP::__construct
     * @covers \GISwrapper\AuthProviderOP::getToken
     * @covers \GISwrapper\AuthProviderOP::generateNewToken
     * @covers \GISwrapper\AuthProviderOP::proceedToken
     * @covers \GISwrapper\AuthProviderOP::setSession
     */
    public function testAuthProviderOPinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderOP(OP_USER, OP_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token, "Token doesn't match format");

        unlink($session);
    }

    /**
     * @covers \GISwrapper\AuthProviderCombined::__construct
     * @covers \GISwrapper\AuthProviderCombined::getToken
     * @covers \GISwrapper\AuthProviderCombined::generateNewToken
     * @covers \GISwrapper\AuthProviderCombined::setSession
     * @covers \GISwrapper\AuthProviderCombined::isEXPA
     */
    public function testAuthProviderCombinedEXPAinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderCombined(EXPA_USER, EXPA_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token, "Token doesn't match format");
        $this->assertTrue($user->isEXPA(), "token is not an EXPA token");

        unlink($session);
    }

    /**
     * @covers \GISwrapper\AuthProviderCombined::__construct
     * @covers \GISwrapper\AuthProviderCombined::getToken
     * @covers \GISwrapper\AuthProviderCombined::generateNewToken
     * @covers \GISwrapper\AuthProviderCombined::setSession
     * @covers \GISwrapper\AuthProviderCombined::isOP
     */
    public function testAuthProviderCombinedOPinvalidSession() {
        // create session file
        $session = './' . md5(microtime) . '.session';
        file_put_contents($session, "...");

        $user = new \GISwrapper\AuthProviderCombined(OP_USER, OP_PW);
        $user->setSession($session);
        $token = $user->getToken();

        $this->assertStringMatchesFormat('%x', $token, "Token doesn't match format");
        $this->assertTrue($user->isOP(), "token is not an OP token");

        unlink($session);
    }

    /**
     * @covers \GISwrapper\AuthProviderShadow::__construct
     * @covers \GISwrapper\AuthProviderShadow::getToken
     * @covers \GISwrapper\AuthProviderShadow::getNewToken
     * @covers \GISwrapper\AuthProviderShadow::getAuthProvider
     */
    public function testAuthProviderShadow() {
        $sub = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
        $user = new \GISwrapper\AuthProviderShadow("sometoken", $sub);
        $this->assertEquals("sometoken", $user->getToken());

        $token = $user->getNewToken();
        $this->assertNotEquals("sometoken", $token);
        $this->assertStringMatchesFormat('%x', $token, "Token doesn't match format");

        $this->assertEquals($sub, $user->getAuthProvider());
    }

    /**
     * @expectedException \GISwrapper\InvalidCredentialsException
     * @expectedExceptionMessage Could not get token from sub auth provider
     */
    public function testAuthProviderShadowException() {
        $user = new \GISwrapper\AuthProviderShadow("sometoken");
        $user->getNewToken();
    }
}