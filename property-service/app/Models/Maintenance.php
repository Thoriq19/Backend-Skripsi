<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $table = 'maintenance';

    protected $fillable = [
        'deskripsi',
        'biaya',
        'tanggal_perbaikan',
        'status',
        'id_aset',
    ];

    protected function casts(): array
    {
        return [
            'biaya'              => 'decimal:2',
            'tanggal_perbaikan'  => 'date',
        ];
    }

    /**
     * Get the aset being maintained.
     */
    public function aset()
    {
        return $this->belongsTo(Aset::class, 'id_aset');
    }
}
