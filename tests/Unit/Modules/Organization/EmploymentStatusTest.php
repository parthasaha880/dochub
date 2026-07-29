<?php

namespace Tests\Unit\Modules\Organization;

use App\Modules\Organization\Enums\EmploymentStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmploymentStatusTest extends TestCase
{
    #[Test]
    public function it_exposes_expected_employment_statuses(): void
    {
        $values = array_map(fn (EmploymentStatus $status) => $status->value, EmploymentStatus::cases());

        $this->assertContains('active', $values);
        $this->assertContains('resigned', $values);
        $this->assertContains('terminated', $values);
    }
}
