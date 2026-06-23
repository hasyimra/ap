<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only di app ini — tabel prc_purchase_orders dimiliki & ditulis oleh app prc.
 * ap hanya membaca ini untuk dropdown referensi/tag opsional di AP Invoice (lihat plan:
 * tidak ada auto-pull baris, PO cuma jadi penanda/referensi).
 */
class PurchaseOrder extends Model
{
    protected $table = 'prc_purchase_orders';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
