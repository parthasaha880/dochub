<?php

namespace App\Modules\Archive\Services;

use App\Modules\Archive\Models\DocumentNumberSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberingService
{
    /**
     * Allocate next sequential number: SERIES/YYYY/000001
     */
    public function next(string $organizationId, string $series = 'ARC'): string
    {
        $series = strtoupper(trim($series));
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($organizationId, $series, $year) {
            $sequence = DocumentNumberSequence::query()
                ->where('organization_id', $organizationId)
                ->where('series', $series)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentNumberSequence::query()->create([
                    'organization_id' => $organizationId,
                    'series' => $series,
                    'year' => $year,
                    'last_number' => 0,
                ]);

                $sequence = DocumentNumberSequence::query()
                    ->whereKey($sequence->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $sequence->last_number = (int) $sequence->last_number + 1;
            $sequence->save();

            return sprintf('%s/%d/%06d', $series, $year, $sequence->last_number);
        });
    }
}
