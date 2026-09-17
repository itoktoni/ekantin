<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseModel
{
    protected $table = 'audit_log';

    protected $primaryKey = 'audit_id';

    protected $fillable = [
        'audit_aksi',
        'audit_model',
        'audit_id_record',
        'audit_lama',
        'audit_baru',
        'audit_id_user',
    ];

    public static $filterColumns = [
        'audit_aksi' => 'Aksi',
        'audit_model' => 'Model',
    ];

    public static $sortColumns = [
        'audit_aksi',
        'audit_model',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'audit_lama' => 'array',
            'audit_baru' => 'array',
        ];
    }

    public static function field_name(): string
    {
        return 'audit_aksi';
    }

    public function rules(): array
    {
        return [
            'audit_aksi' => 'required|string|max:40',
            'audit_model' => 'required|string|max:60',
            'audit_id_record' => 'nullable|integer|min:0',
            'audit_lama' => 'nullable|array',
            'audit_baru' => 'nullable|array',
            'audit_id_user' => 'nullable|exists:users,id',
        ];
    }

    public function hasUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audit_id_user', 'id');
    }
}
