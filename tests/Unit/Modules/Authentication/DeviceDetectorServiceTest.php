<?php

namespace Tests\Unit\Modules\Authentication;

use App\Modules\Authentication\Services\DeviceDetectorService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceDetectorServiceTest extends TestCase
{
    #[Test]
    public function it_detects_chrome_on_windows(): void
    {
        $service = new DeviceDetectorService;
        $request = Request::create('/', 'GET', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        ]);

        $result = $service->detect($request);

        $this->assertSame('Chrome', $result['browser']);
        $this->assertSame('Windows', $result['platform']);
        $this->assertSame('desktop', $result['device_type']);
    }

    #[Test]
    public function it_creates_stable_fingerprint(): void
    {
        $service = new DeviceDetectorService;
        $request = Request::create('/', 'GET', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 TestAgent',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
        ]);

        $first = $service->fingerprint($request);
        $second = $service->fingerprint($request);

        $this->assertSame($first, $second);
        $this->assertSame(64, strlen($first));
    }
}
