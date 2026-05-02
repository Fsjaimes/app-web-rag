<?php

declare(strict_types=1);

namespace App\Modules\DocumentTypes\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\DocumentTypes\Domain\ValueObjects\{DocumentTypeAffectsInventory, DocumentTypeHasPrefix, DocumentTypeHasDate, DocumentTypeStatus};

class DocumentType extends Model
{
    use SoftDeletes;

    protected $table = 'tut_document_types';
    protected $connection = 'tenant';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'uuid',
        'name',
        'prefix',
        'affects_inventory',
        'inventory_movement_type',
        'has_prefix',
        'has_date',
        'date_format',
        'length_sequence',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
    ];
}