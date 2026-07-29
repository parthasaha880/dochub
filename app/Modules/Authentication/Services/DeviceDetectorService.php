<?php

namespace App\Modules\Authentication\Services;

use Illuminate\Http\Request;

class DeviceDetectorService
{
    public function fingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->userAgent() ?? 'unknown',
            $request->header('Accept-Language', 'unknown'),
        ]));
    }

    /**
     * @return array{device_type: string, browser: string, platform: string, device_name: string}
     */
    public function detect(Request $request): array
    {
        $agent = $request->userAgent() ?? '';

        $browser = $this->matchFirst($agent, [
            'Edg' => 'Edge',
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'Opera' => 'Opera',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
        ], 'Unknown');

        $platform = $this->matchFirst($agent, [
            'Windows' => 'Windows',
            'Macintosh' => 'macOS',
            'iPhone' => 'iOS',
            'iPad' => 'iOS',
            'Android' => 'Android',
            'Linux' => 'Linux',
        ], 'Unknown');

        $deviceType = match (true) {
            str_contains($agent, 'Mobile') || str_contains($agent, 'Android') => 'mobile',
            str_contains($agent, 'Tablet') || str_contains($agent, 'iPad') => 'tablet',
            default => 'desktop',
        };

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'device_name' => trim("{$browser} on {$platform}"),
        ];
    }

    /**
     * @param  array<string, string>  $patterns
     */
    private function matchFirst(string $agent, array $patterns, string $default): string
    {
        foreach ($patterns as $needle => $label) {
            if (str_contains($agent, $needle)) {
                return $label;
            }
        }

        return $default;
    }
}
