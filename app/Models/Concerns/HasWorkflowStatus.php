<?php

namespace App\Models\Concerns;

trait HasWorkflowStatus
{
    public static function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'selesai' => 'Selesai',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'diajukan' => 'warning',
            'disetujui' => 'info',
            'ditolak' => 'danger',
            'selesai' => 'success',
            default => 'light',
        };
    }
}
