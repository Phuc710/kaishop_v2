<?php

namespace App\Helpers;

class UserAgentParser
{
    /**
     * Parse the User-Agent string to extract OS, Browser, and Device Type.
     *
     * @param string $userAgent
     * @return array
     */
    public static function parse(string $userAgent): array
    {
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';
        $deviceType = 'Desktop'; // Default assumption

        // OS Detection
        $osMappings = [
            '/windows nt 10.0/i' => 'Windows 10/11',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone (iOS)',
            '/ipod/i' => 'iPod (iOS)',
            '/ipad/i' => 'iPad (iOS)',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile',
        ];

        foreach ($osMappings as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $os = $value;
                break;
            }
        }

        // Browser Detection
        $browserMappings = [
            '/msie/i' => 'Internet Explorer',
            '/trident/i' => 'Internet Explorer',
            '/edg/i' => 'Microsoft Edge',
            '/coc_coc_browser/i' => 'Cốc Cốc',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/opera|opr/i' => 'Opera',
            '/yabrowser/i' => 'Yandex',
            '/ucbrowser/i' => 'UC Browser',
        ];

        foreach ($browserMappings as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $browser = $value;
                // Safari and Chrome share some WebKit strings, so order matters slightly
                // Chrome contains "Safari", so checking Edge/Cốc Cốc/Chrome first is usually better.
                // The array order is important here.
                break;
            }
        }

        // Refine Browser Detection (Safari vs Chrome vs Edge)
        // Since Chrome user agent contains "Safari", we need to be careful if it matched Safari but is actually Chrome
        if ($browser === 'Safari' && preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        }

        if ($browser === 'Chrome' && preg_match('/edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        }

        if ($browser === 'Chrome' && preg_match('/coc_coc_browser/i', $userAgent)) {
            $browser = 'Cốc Cốc';
        }

        if ($browser === 'Chrome' && preg_match('/opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Device Type Detection
        if (preg_match('/mobile|android|touch|webos|hpwos/i', $userAgent)) {
            $deviceType = 'Mobile';
        }
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent) || (preg_match('/android/i', $userAgent) && !preg_match('/mobile/i', $userAgent))) {
            $deviceType = 'Tablet';
        }

        return [
            'os' => $os,
            'browser' => $browser,
            'type' => $deviceType,
        ];
    }
}
