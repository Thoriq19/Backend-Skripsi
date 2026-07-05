<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kamar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kamar';

    protected $fillable = [
        'nomor_kamar',
        'kapasitas_kamar',
        'harga_kamar',
        'status_kamar',
        'deskripsi_kamar',
        'id_kos',
    ];

    protected function casts(): array
    {
        return [
            'harga_kamar'     => 'decimal:2',
            'kapasitas_kamar' => 'integer',
        ];
    }

    /**
     * Get the kos that owns this kamar.
     */
    public function kos()
    {
        return $this->belongsTo(Kos::class, 'id_kos');
    }

    /**
     * Scope: kamar tersedia only.
     */
    public function scopeTersedia($query)
    {
        return $query->where('status_kamar', 'tersedia');
    }
}
