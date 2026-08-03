<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Archive\Enums\LocationType;
use App\Modules\Archive\Models\ArchiveLocation;
use App\Modules\Archive\Services\ArchiveService;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentCategory;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Seeder;

class ArchiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('code', 'EDAMS')->first();
        $admin = User::query()->where('email', 'admin@edams.local')->first()
            ?? User::query()->where('email', 'parthasaha31@gmail.com')->first();

        if (! $org || ! $admin) {
            return;
        }

        if (! DocumentCategory::query()->where('organization_id', $org->id)->exists()) {
            $root = DocumentCategory::query()->create([
                'organization_id' => $org->id,
                'code' => 'GEN',
                'name' => 'General Records',
                'description' => 'Default archive classification',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            DocumentCategory::query()->create([
                'organization_id' => $org->id,
                'parent_id' => $root->id,
                'code' => 'FIN',
                'name' => 'Finance',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            DocumentCategory::query()->create([
                'organization_id' => $org->id,
                'parent_id' => $root->id,
                'code' => 'HR',
                'name' => 'Human Resources',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        if (ArchiveLocation::query()->where('organization_id', $org->id)->exists()) {
            return;
        }

        $service = app(ArchiveService::class);

        $room = $service->createLocation([
            'organization_id' => $org->id,
            'type' => LocationType::Room->value,
            'code' => 'R-001',
            'name' => 'Central Archive Room',
            'capacity' => 50,
            'description' => 'Main records room',
        ], $admin);

        $rack = $service->createLocation([
            'parent_id' => $room->id,
            'type' => LocationType::Rack->value,
            'code' => 'RK-01',
            'name' => 'Rack A',
        ], $admin);

        $shelf = $service->createLocation([
            'parent_id' => $rack->id,
            'type' => LocationType::Shelf->value,
            'code' => 'SH-01',
            'name' => 'Shelf 1',
        ], $admin);

        $box = $service->createLocation([
            'parent_id' => $shelf->id,
            'type' => LocationType::Box->value,
            'code' => 'BX-01',
            'name' => 'Box 2024-A',
            'capacity' => 100,
        ], $admin);

        $file = $service->createLocation([
            'parent_id' => $box->id,
            'type' => LocationType::File->value,
            'code' => 'F-01',
            'name' => 'File Bundle 01',
        ], $admin);

        $docs = Document::query()
            ->where('organization_id', $org->id)
            ->whereNull('location_id')
            ->latest()
            ->limit(3)
            ->get();

        foreach ($docs as $index => $document) {
            $target = $index === 0 ? $file : $box;
            $service->assignDocumentLocation(
                $document->id,
                $target->id,
                $document->path ? 'hybrid' : 'physical',
                $admin
            );

            if ($index === 0) {
                $service->archiveDocument($document->id, $admin, $target->id);
            }
        }
    }
}
