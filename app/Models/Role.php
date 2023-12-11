<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    protected $hidden = [
        'pivot'
    ];

    //Relacion n:n
    public function clients(): BelongsToMany{
        return $this->belongsToMany(Client::class);
    }

    public function members(): BelongsToMany{
        return $this->belongsToMany(Member::class);
    }

    public function organizations(): BelongsToMany{
        return $this->belongsToMany(Organization::class);
    }
}
