<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AutoNumberService
{
    private const PREFIXES = [
        'ap_invoice' => 'BIL',
        'ap_payment' => 'PAY',
    ];

    public function generate(string $documentType): string
    {
        $prefix = self::PREFIXES[$documentType] ?? throw new \InvalidArgumentException("Unknown document type: {$documentType}");
        $period = now()->format('Ym');

        $nextNumber = DB::transaction(function () use ($documentType, $period) {
            $sequence = DB::table('ap_document_number_sequences')
                ->where('document_type', $documentType)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($sequence) {
                $next = $sequence->last_number + 1;
                DB::table('ap_document_number_sequences')
                    ->where('id', $sequence->id)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            } else {
                $next = 1;
                DB::table('ap_document_number_sequences')->insert([
                    'document_type' => $documentType,
                    'period' => $period,
                    'last_number' => $next,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $next;
        });

        return sprintf('%s-%s-%04d', $prefix, $period, $nextNumber);
    }
}
