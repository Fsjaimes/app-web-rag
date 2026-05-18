<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class ConversationModel extends Model
{
    protected $table = 'conversations';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessageModel::class, 'conversation_id');
    }
}
