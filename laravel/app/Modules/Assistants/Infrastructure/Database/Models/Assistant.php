<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assistant extends Model
{
    use SoftDeletes;

    protected $table = 'assistants';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        // TODO: Agregar campos fillable
    ];

    protected $casts = [
        // TODO: Agregar casts si es necesario
        // 'created_at' => 'datetime',
        // 'updated_at' => 'datetime',
    ];
}