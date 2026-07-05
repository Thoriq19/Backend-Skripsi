<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aset';

    protected $fillable = [
        'nama_aset',
        'tanggal_pembelian',
        'harga',
        'kondisi',
        'id_kos',
    ];

    protected function casts(): array
    {
        return [
            'harga'              => 'decimal:2',
            'tanggal_pembelian'  => 'date',
        ];
    }

    /**
     * Get the kos that owns this aset.
     */
    public function kos()
    {
        return $this->belongsTo(Kos::class, 'id_kos');
    }

    /**
     * Get the maintenance records for this aset.
     */
    public function maintenance()
    {
        return $this->hasMany(Maintenance::class, 'id_aset');
    }

    /**
     * Get the laporan kerusakan for this aset.
     */
    public function laporanKerusakan()
    {
        return $this->hasMany(\App\Models\LaporanKerusakan::class, 'id_aset');
    }
}
