<?php

namespace Tests\Unit\Modules\Documents;

use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Enums\ConfidentialityLevel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentEnumTest extends TestCase
{
    #[Test]
    public function it_defines_workflow_and_confidentiality_values(): void
    {
        $this->assertSame('draft', ApprovalStatus::Draft->value);
        $this->assertSame('approved', ApprovalStatus::Approved->value);
        $this->assertSame('confidential', ConfidentialityLevel::Confidential->value);
    }
}
