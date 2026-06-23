<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlAccount extends Model
{
    use SoftDeletes;

    protected $table = 'gl_accounts';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'parent_id');
    }
}
