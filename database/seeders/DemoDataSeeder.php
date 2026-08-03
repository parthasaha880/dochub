<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Enums\ConfidentialityLevel;
use App\Modules\Documents\Enums\DocumentStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentVersion;
use App\Modules\Documents\Models\Folder;
use App\Modules\Organization\Models\Branch;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure prerequisites exist even if seeders were run out of order.
        if (! Organization::query()->where('code', 'EDAMS')->exists()) {
            $this->call(OrganizationDemoSeeder::class);
        }

        if (! User::query()->role('super_admin')->exists()
            && ! User::query()->whereIn('email', ['admin@edams.local', 'parthasaha31@gmail.com'])->exists()) {
            $this->call(AuthDemoSeeder::class);
        }

        $org = Organization::query()->where('code', 'EDAMS')->first();
        $admin = User::query()->where('email', 'admin@edams.local')->first()
            ?? User::query()->role('super_admin')->first()
            ?? User::query()->where('email', 'parthasaha31@gmail.com')->first();

        if (! $org || ! $admin) {
            $this->command?->error('DemoDataSeeder aborted: organization or admin user still missing after prerequisites.');

            return;
        }

        if (Document::query()->where('organization_id', $org->id)->where('remarks', 'DEMO_SEED')->exists()) {
            $this->command?->info('Demo documents already present — skipping recreate.');

            return;
        }

        $branch = Branch::query()->where('organization_id', $org->id)->where('code', 'HQ')->first();

        $departments = collect([
            ['code' => 'PUR', 'name' => 'PURCHASE'],
            ['code' => 'LEG', 'name' => 'LEGAL'],
            ['code' => 'ACC', 'name' => 'ACCOUNTS'],
            ['code' => 'HR', 'name' => 'HR'],
            ['code' => 'IT', 'name' => 'Information Technology'],
            ['code' => 'DAT', 'name' => 'DAT MUMBAI'],
            ['code' => 'OPS', 'name' => 'OPERATIONS'],
            ['code' => 'FIN', 'name' => 'FINANCE'],
        ])->map(function (array $row) use ($org, $branch) {
            return Department::query()->updateOrCreate(
                ['organization_id' => $org->id, 'code' => $row['code']],
                [
                    'branch_id' => $branch?->id,
                    'name' => $row['name'],
                    'is_active' => true,
                ]
            );
        });

        $users = collect([
            ['name' => 'Asha Mehta', 'email' => 'asha@edams.local', 'role' => 'officer'],
            ['name' => 'Rahul Khan', 'email' => 'rahul@edams.local', 'role' => 'manager'],
            ['name' => 'Priya Sen', 'email' => 'priya@edams.local', 'role' => 'officer'],
            ['name' => 'Imran Ali', 'email' => 'imran@edams.local', 'role' => 'data_entry_operator'],
            ['name' => 'Nisha Roy', 'email' => 'nisha@edams.local', 'role' => 'department_admin'],
            ['name' => 'David Chen', 'email' => 'david@edams.local', 'role' => 'officer'],
            ['name' => 'Sara Ahmed', 'email' => 'sara@edams.local', 'role' => 'viewer'],
        ])->map(function (array $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'username' => Str::before($row['email'], '@'),
                    'password' => Hash::make('Password@12345'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'password_changed_at' => now(),
                ]
            );
            $user->syncRoles([$row['role']]);

            return $user;
        });

        $uploaders = $users->push($admin)->values();

        $folders = collect(['Contracts', 'Invoices', 'HR Records', 'Legal', 'Backups', 'Media'])->map(
            fn (string $name) => Folder::query()->firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'parent_id' => null,
                    'name' => $name,
                ],
                [
                    'created_by' => $admin->id,
                ]
            )
        );

        $documentBlueprints = [
            ['type' => 'SALES INVOICE', 'ext' => 'pdf', 'mime' => 'application/pdf', 'weight' => 40, 'size' => [80_000, 450_000]],
            ['type' => 'AGREEMENT', 'ext' => 'pdf', 'mime' => 'application/pdf', 'weight' => 30, 'size' => [120_000, 2_200_000]],
            ['type' => 'PENSION RECORD', 'ext' => 'pdf', 'mime' => 'application/pdf', 'weight' => 20, 'size' => [90_000, 800_000]],
            ['type' => 'CERTIFICATES', 'ext' => 'pdf', 'mime' => 'application/pdf', 'weight' => 18, 'size' => [50_000, 600_000]],
            ['type' => 'CR', 'ext' => 'pdf', 'mime' => 'application/pdf', 'weight' => 25, 'size' => [40_000, 350_000]],
            ['type' => 'CONFIDENTIAL REPORT', 'ext' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'weight' => 22, 'size' => [100_000, 1_500_000]],
            ['type' => 'NO SEVARTH ID_CR', 'ext' => 'pdf', 'mime' => 'application/pdf', 'weight' => 55, 'size' => [200_000, 4_500_000]],
            ['type' => 'POLICY', 'ext' => 'pdf', 'mime' => 'application/pdf', 'weight' => 15, 'size' => [70_000, 900_000]],
            ['type' => 'SPREADSHEET', 'ext' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'weight' => 20, 'size' => [30_000, 2_000_000]],
            ['type' => 'PRESENTATION', 'ext' => 'pptx', 'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'weight' => 12, 'size' => [500_000, 3_500_000]],
            ['type' => 'ARCHIVE BUNDLE', 'ext' => 'zip', 'mime' => 'application/zip', 'weight' => 10, 'size' => [1_000_000, 8_000_000]],
            ['type' => 'DATABASE DUMP', 'ext' => 'sql', 'mime' => 'application/sql', 'weight' => 8, 'size' => [2_000_000, 12_000_000]],
            ['type' => 'BACKUP SET', 'ext' => 'bak', 'mime' => 'application/octet-stream', 'weight' => 8, 'size' => [3_000_000, 15_000_000]],
            ['type' => 'AUDIO NOTE', 'ext' => 'mp3', 'mime' => 'audio/mpeg', 'weight' => 6, 'size' => [1_000_000, 6_000_000]],
            ['type' => 'VIDEO BRIEF', 'ext' => 'mp4', 'mime' => 'video/mp4', 'weight' => 6, 'size' => [4_000_000, 18_000_000]],
            ['type' => 'IMAGE SCAN', 'ext' => 'jpg', 'mime' => 'image/jpeg', 'weight' => 14, 'size' => [200_000, 3_500_000]],
        ];

        // Only statuses that do not require a live workflow_instance.
        // "under_review" must come from WorkflowService::submitDocument.
        $approvals = [
            ApprovalStatus::Draft,
            ApprovalStatus::Draft,
            ApprovalStatus::Approved,
            ApprovalStatus::Approved,
            ApprovalStatus::Approved,
            ApprovalStatus::Rejected,
            ApprovalStatus::Returned,
        ];

        $disk = config('filesystems.default', 'local');
        $created = 0;

        foreach ($documentBlueprints as $blueprint) {
            for ($i = 1; $i <= $blueprint['weight']; $i++) {
                $uploader = $uploaders->random();
                $department = $departments->random();
                $folder = $folders->random();
                $size = random_int($blueprint['size'][0], $blueprint['size'][1]);
                $ext = $blueprint['ext'];
                $filename = Str::uuid()->toString().'.'.$ext;
                $directory = "documents/{$org->id}/demo/".now()->format('Y/m');
                $path = $directory.'/'.$filename;

                Storage::disk($disk)->put($path, '');
                $this->writeFakeFile($disk, $path, $size);

                $daysAgo = random_int(0, 120);
                $createdAt = now()->subDays($daysAgo)->subMinutes(random_int(0, 1400));

                $document = Document::query()->create([
                    'organization_id' => $org->id,
                    'folder_id' => $folder->id,
                    'department_id' => $department->id,
                    'owner_id' => $uploader->id,
                    'uploader_id' => $uploader->id,
                    'title' => $blueprint['type'].' #'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'reference_no' => strtoupper(Str::substr($blueprint['type'], 0, 3)).'-'.random_int(1000, 9999),
                    'archive_no' => 'ARC-'.random_int(10000, 99999),
                    'barcode' => 'BC-'.Str::upper(Str::random(8)),
                    'qr_code' => 'QR-'.Str::upper(Str::random(8)),
                    'description' => 'Demo seeded document for dashboard reports',
                    'keywords' => 'demo,seed,'.$ext.','.$department->code,
                    'confidentiality_level' => fake()->randomElement([
                        ConfidentialityLevel::Internal,
                        ConfidentialityLevel::Confidential,
                        ConfidentialityLevel::Restricted,
                        ConfidentialityLevel::Public,
                    ]),
                    'document_type' => $blueprint['type'],
                    'version' => 1,
                    'approval_status' => fake()->randomElement($approvals),
                    'status' => DocumentStatus::Active,
                    'remarks' => 'DEMO_SEED',
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => Str::slug($blueprint['type'])."-{$i}.{$ext}",
                    'mime_type' => $blueprint['mime'],
                    'extension' => $ext,
                    'size' => $size,
                    'checksum' => hash('sha256', $path.$size),
                    'created_by' => $uploader->id,
                ]);

                $document->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();

                DocumentVersion::query()->create([
                    'document_id' => $document->id,
                    'version_number' => 1,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $document->original_name,
                    'mime_type' => $document->mime_type,
                    'extension' => $ext,
                    'size' => $size,
                    'checksum' => $document->checksum,
                    'change_summary' => 'Initial demo upload',
                    'uploaded_by' => $uploader->id,
                ]);

                $created++;
            }
        }

        $this->command?->info("DemoDataSeeder created {$created} documents, {$departments->count()} departments, {$users->count()} users.");
    }

    private function writeFakeFile(string $disk, string $path, int $size): void
    {
        $fullPath = Storage::disk($disk)->path($path);
        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $handle = fopen($fullPath, 'wb');
        $remaining = $size;
        $chunk = str_repeat("\0", 65_536);

        while ($remaining > 0) {
            $len = min(65_536, $remaining);
            fwrite($handle, $len === 65_536 ? $chunk : str_repeat("\0", $len));
            $remaining -= $len;
        }

        fclose($handle);
    }
}
