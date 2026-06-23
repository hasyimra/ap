<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApInvoiceLine extends Model
{
    protected $table = 'ap_invoice_lines';

    protected $fillable = ['ap_invoice_id', 'gl_account_id', 'tax_id', 'description', 'amount'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ApInvoice::class, 'ap_invoice_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
