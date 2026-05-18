<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AcademicDocument extends Model
{
    protected $table = 'academic_documents';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'uuid',
        'title',
        'filename',
        'mime_type',
        'size_bytes',
        'status',
        'error_message',
        'chroma_ids',
        'uploaded_by',
    ];

    protected $casts = [
        'chroma_ids'   => 'array',
        'size_bytes'   => 'integer',
        'uploaded_by'  => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}