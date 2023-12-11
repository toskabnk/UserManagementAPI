<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'client_id'
    ];

    protected $hidden = [
        'pivot',
    ];

    //Relacion n:n
    public function members(): BelongsToMany{
        return $this->belongsToMany(Member::class);
    }

    public function client(): BelongsTo{
        return $this->belongsTo(Client::class);
    }
}
