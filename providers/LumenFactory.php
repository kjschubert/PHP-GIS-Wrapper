<?php
namespace GISwrapper\Providers;

use GISwrapper\AuthProviderEXPA;
use GISwrapper\AuthProviderOP;
use GISwrapper\AuthProviderCombined;
use GISwrapper\AuthProviderShadow;
use GISwrapper\AuthProviderNationalIdentity;
use GISwrapper\GIS;
use Illuminate\Support\Facades\Cache;

/**
 * Class LumenFactory
 * 
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper\Providers
 * @version 0.1
 */
class LumenFactory
{
    /**
     * @var array
     */
    private static $_cache;

    public static function getCache() {
        if(self::$_cache == null) {
            if(file_exists(dirname(__DIR__) . '/cache.dat')) {
                self::$_cache = unserialize(file_get_contents(dirname(__DIR__) . '/cache.dat'));
            }
            if(self::$_cache == null || self::$_cache == false) {
                self::$_cache = GIS::generateFullCache();
                file_put_contents(dirname(__DIR__) . '/cache.dat', serialize(self::$_cache));
            }
        }
        return self::$_cache;
    }

    /**
     * @param null|integer $userId
     * @return GIS
     */
    public static function getInstance($userId = null) {
        if($userId === null) {
            $token = Cache::get('gis_token');
            if($token != null) {
                $user = new AuthProviderShadow($token, new AuthProviderEXPA(env('GIS_USER'), env('GIS_PASS')));
            } else {
                $user = new AuthProviderEXPA(env('GIS_USER'), env('GIS_PASS'));
                Cache::put('gis_token', $user->getToken(), $user->getExpiresAt());
            }
        } else {
            $token = Cache::get('gis_token:' . $userId);
            $url = str_replace('%USER_ID%', $userId, env('NATIONAL_AUTH_URL'));
            if($token != null) {
                $user = new AuthProviderShadow($token, new AuthProviderNationalIdentity($url));
            } else {
                $user = new AuthProviderNationalIdentity($url);
                try {
                    $token = $user->getToken();
                    Cache::put('gis_token:' . $userId, $token, $user->getExpiresAt());
                } catch (\Exception $e) {}
            }
        }
        return new GIS($user, self::getCache());
    }

    /**
     * @param string $username
     * @param null|string $password
     * @param string $type expa|op|combined
     * @return GIS
     */
    public static function getUserInstance($username, $password = null, $type = 'combined') {
        switch($type) {
            case 'expa':
                $user = new AuthProviderEXPA($username, $password);
                break;

            case 'op':
                $user = new AuthProviderOP($username, $password);
                break;

            default:
                $type = 'combined';
                $user = new AuthProviderCombined($username, $password);
        }
        $token = Cache::get('gis_token_' . $type . ':' . $username);
        if($token != null) {
            $user = new AuthProviderShadow($token, $user);
        } else {
            try {
                $token = $user->getToken();
                Cache::put('gis_token_' . $type . ':' . $username, $token, $user->getExpiresAt());
            } catch (\Exception $e) {}  // just try if we get a token to put it in cache, but don't fire any exception
        }
        return new GIS($user, self::getCache());
    }
}