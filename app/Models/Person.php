<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
    protected $table = 'people';

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date:Y-m-d',
        'dod' => 'date:Y-m-d',
        'cemetery_location' => 'array',
    ];

    public function father(): BelongsTo
    {
        return $this->belongsTo(self::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'father_id')
            ->orWhere('mother_id', $this->getKey());
    }

    public function couplesAsHusband(): HasMany
    {
        return $this->hasMany(Couple::class, 'husband_id');
    }

    public function couplesAsWife(): HasMany
    {
        return $this->hasMany(Couple::class, 'wife_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
