<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'child_id', 'created_by', 'category', 'action_plan', 'target_date', 'status', 'notes',
])]
class FollowUpRecord extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'status' => FollowUpStatus::class,
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
