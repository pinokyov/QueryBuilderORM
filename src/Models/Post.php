<?php

namespace App\Models;

use App\Orm\Model;
use App\Orm\Relations\BelongsTo;

class Post extends Model
{
    protected string $table = 'posts';
    protected string $primaryKey = 'id';
    protected array $fillable = ['title', 'content', 'user_id'];
    
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
