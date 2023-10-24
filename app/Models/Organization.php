<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'

    ];

    protected $hidden = [
        'pivot',
    ];

    //Relacion n:n
    public function users(): BelongsToMany{
        return $this->belongsToMany(User::class);
    }
}
