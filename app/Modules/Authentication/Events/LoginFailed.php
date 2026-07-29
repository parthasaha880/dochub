<?php

namespace App\Modules\Authentication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public string $ipAddress,
        public string $reason,
        public ?string $userAgent = null,
    ) {}
}
