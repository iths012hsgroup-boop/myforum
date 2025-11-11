<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $topik_title
 * @property bool   $status
 * @property bool   $soft_delete
 * @property-read string $status_label
 */
class Topik extends Model
{
    public const TABLE          = 'tbhs_topik';
    public const COL_TITLE      = 'topik_title';
    public const COL_STATUS     = 'status';
    public const COL_SOFT_DEL   = 'soft_delete';
    private const SCOPE_VISIBLE = 'not_deleted';

    protected $table = self::TABLE;

    // gunakan kolom baru
    protected $fillable = [self::COL_TITLE, self::COL_STATUS, self::COL_SOFT_DEL];

    // default value, jika dibutuhkan
    protected $attributes = [
        self::COL_STATUS   => 0,
        self::COL_SOFT_DEL => 0,
    ];

    protected $casts = [
        self::COL_STATUS   => 'bool',   // flag -> bool lebih natural
        self::COL_SOFT_DEL => 'bool',
    ];

    protected static function booted(): void
    {
        // tampilkan hanya yang belum dihapus
        static::addGlobalScope(self::SCOPE_VISIBLE, function (Builder $q): void {
            $q->where(self::COL_SOFT_DEL, false);
        });
    }

    /** Pencarian berdasarkan judul topik */
    public function scopeSearch(Builder $q, ?string $s): Builder
    {
        $s = trim((string) $s);
        return $s !== '' ? $q->where(self::COL_TITLE, 'like', "%{$s}%") : $q;
    }

    /** Hanya yang aktif */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where(self::COL_STATUS, true);
    }

    /** Hanya yang nonaktif */
    public function scopeInactive(Builder $q): Builder
    {
        return $q->where(self::COL_STATUS, false);
    }

    /** Sertakan yang sudah di-"hapus" (hilangkan global scope) */
    public function scopeWithDeleted(Builder $q): Builder
    {
        return $q->withoutGlobalScope(self::SCOPE_VISIBLE);
    }

    /** Hanya yang sudah di-"hapus" */
    public function scopeOnlyDeleted(Builder $q): Builder
    {
        return $this->scopeWithDeleted($q)->where(self::COL_SOFT_DEL, true);
    }

    /** Tandai sebagai dihapus (soft delete berbasis flag integer/bool) */
    public function markAsDeleted(): bool
    {
        $this->setAttribute(self::COL_SOFT_DEL, true);
        return (bool) $this->save();
    }

    /** Pulihkan dari soft delete */
    public function restore(): bool
    {
        $this->setAttribute(self::COL_SOFT_DEL, false);
        return (bool) $this->save();
    }

    /** Label status helper */
    public function getStatusLabelAttribute(): string
    {
        return $this->getAttribute(self::COL_STATUS) ? 'Aktif' : 'Nonaktif';
    }
}
