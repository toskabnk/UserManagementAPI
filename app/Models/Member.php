<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'surname',
        'birth_date',
        'roles',
        'organizations',
        'user_id'
    ];
    
    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function roles(): BelongsToMany{
        return $this->belongsToMany(Role::class);
    }

    public function organizations(): BelongsToMany{
        return $this->belongsToMany(Organization::class);
    }
}
