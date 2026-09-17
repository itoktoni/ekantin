<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeHistory extends BaseModel
{
    protected $table = 'fee_history';

    protected $primaryKey = 'history_id';

    protected $fillable = [
        'history_field',
        'history_lama',
        'history_baru',
        'history_id_admin',
    ];

    public static $filterColumns = [
        'history_field' => 'Field',
    ];

    public static $sortColumns = [
        'history_field',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'history_lama' => 'integer',
            'history_baru' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'history_field';
    }

    public function rules(): array
    {
        return [
            'history_field' => 'required|string|max:40',
            'history_lama' => 'required|integer|min:0',
            'history_baru' => 'required|integer|min:0',
            'history_id_admin' => 'nullable|exists:users,id',
        ];
    }

    public function hasAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'history_id_admin', 'id');
    }
}
