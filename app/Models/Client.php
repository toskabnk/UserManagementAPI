<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id'
    ];

    public function roles(): BelongsToMany{
        return $this->belongsToMany(Role::class);
    }

    public function organizations(): HasMany{
        return $this->hasMany(Organization::class);
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
}
