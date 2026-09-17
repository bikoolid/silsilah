<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Couple extends Model
{
    protected $table = 'couples';

    protected $guarded = [];

    protected $casts = [
        'marriage_date' => 'date:Y-m-d',
        'divorce_date' => 'date:Y-m-d',
    ];

    public function husband(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'husband_id');
    }

    public function wife(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'wife_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Person::class, 'parents_couple_id');
    }
}
