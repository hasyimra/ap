<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApPaymentAllocation extends Model
{
    protected $table = 'ap_payment_allocations';

    protected $fillable = [
        'ap_payment_id', 'ap_invoice_id', 'amount',
        'disc_taken_amount', 'write_off_amount', 'write_off_gl_account_id',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ApPayment::class, 'ap_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ApInvoice::class, 'ap_invoice_id');
    }

    public function writeOffGlAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'write_off_gl_account_id');
    }
}
