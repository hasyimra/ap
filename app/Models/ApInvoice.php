<?php

namespace App\Models;

use App\Models\Concerns\HasWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApInvoice extends Model
{
    use HasWorkflowStatus, SoftDeletes;

    protected $table = 'ap_invoices';

    protected $fillable = [
        'invoice_no', 'vendor_id', 'description', 'prc_purchase_order_id',
        'invoice_date', 'due_date', 'status',
        'pph_type', 'pph_rate', 'pph_amount', 'withholding_cert_no',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'prc_purchase_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ApInvoiceLine::class, 'ap_invoice_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ApPaymentAllocation::class, 'ap_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->lines->sum(fn (ApInvoiceLine $line) => $line->amount);
    }

    public function getTaxAmountAttribute(): float
    {
        return $this->lines->sum(function (ApInvoiceLine $line) {
            return $line->tax ? round($line->amount * $line->tax->rate / 100, 2) : 0;
        });
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal + $this->tax_amount;
    }

    public function getPaidAmountAttribute(): float
    {
        return $this->allocations->sum('amount');
    }

    public function getDiscTakenAmountAttribute(): float
    {
        return $this->allocations->sum('disc_taken_amount');
    }

    public function getWriteOffAmountAttribute(): float
    {
        return $this->allocations->sum('write_off_amount');
    }

    public function getOwingAttribute(): float
    {
        // PPh withholding bukan dibayar tunai ke vendor — itu disetor ke kas negara atas nama
        // vendor, jadi mengurangi sisa yang benar-benar harus dibayar (owing), bukan ditambahkan.
        return round($this->total - $this->pph_amount - $this->paid_amount - $this->disc_taken_amount - $this->write_off_amount, 2);
    }

    public function getAgeAttribute(): int
    {
        $dueDate = ($this->due_date ?? $this->invoice_date)->copy()->startOfDay();

        return $dueDate->isFuture() ? 0 : $dueDate->diffInDays(now());
    }
}
