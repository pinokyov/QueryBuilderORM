<?php

namespace App\Models;

use App\Orm\Model;
use App\Orm\Relations\HasMany;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['id', 'name', 'email', 'password'];
    protected array $hidden = ['password'];
    
    protected array $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
