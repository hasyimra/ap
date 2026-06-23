<?php

namespace App\Models;

use App\Models\Concerns\HasWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApPayment extends Model
{
    use HasWorkflowStatus, SoftDeletes;

    protected $table = 'ap_payments';

    protected $fillable = [
        'payment_no', 'vendor_id', 'bank_id', 'reference_no', 'payment_date',
        'amount', 'status', 'reconciled', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'approved_at' => 'datetime',
            'reconciled' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ApPaymentAllocation::class, 'ap_payment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getAllocatedAmountAttribute(): float
    {
        return $this->allocations->sum(fn (ApPaymentAllocation $a) => $a->amount + $a->disc_taken_amount + $a->write_off_amount);
    }

    public function getUnallocatedAmountAttribute(): float
    {
        return round($this->amount - $this->allocated_amount, 2);
    }
}
