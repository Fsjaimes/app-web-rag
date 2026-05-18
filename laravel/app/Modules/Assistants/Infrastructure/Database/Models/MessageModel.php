<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageModel extends Model
{
    protected $table = 'messages';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'uuid',
        'conversation_id',
        'role',
        'content',
        'sources',
    ];

    protected $casts = [
        'sources'         => 'array',
        'conversation_id' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ConversationModel::class, 'conversation_id');
    }
}
