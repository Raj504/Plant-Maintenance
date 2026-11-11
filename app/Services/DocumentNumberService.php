<?php

namespace App\Services;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;

/**
 * Generates unique document numbers like MR-2026-0001, WO-2026-0001.
 * Uses a row lock on pm_sequences so two users saving at the same second
 * never get the same number.
 */
class DocumentNumberService
{
    public const PREFIXES = [
        'request'        => 'MR',
        'work_order'     => 'WO',
        'pm_plan'        => 'PM',
        'permit'         => 'PTW',
        'stock'          => 'STK',
        'purchase'       => 'PR',
        'daily_log'      => 'LOG',
        'handover'       => 'HO',
        'amc'            => 'AMC',
        'compliance'     => 'SC',
    ];

    public static function next(string $type): string
    {
        $prefix = self::PREFIXES[$type] ?? strtoupper($type);
        $year   = (int) now()->format('Y');

        return DB::transaction(function () use ($prefix, $year) {
            $sequence = Sequence::where('prefix', $prefix)->where('year', $year)->lockForUpdate()->first();

            if (!$sequence) {
                $sequence = Sequence::create(['prefix' => $prefix, 'year' => $year, 'last_number' => 0]);
                $sequence = Sequence::where('id', $sequence->id)->lockForUpdate()->first();
            }

            $sequence->last_number++;
            $sequence->save();

            return sprintf('%s-%d-%04d', $prefix, $year, $sequence->last_number);
        });
    }
}
