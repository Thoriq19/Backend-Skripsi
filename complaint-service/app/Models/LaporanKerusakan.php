<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanKerusakan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'laporankerusakan';

    protected $fillable = [
        'tanggal_lapor',
        'status_laporan',
        'deskripsi',
        'foto',
        'id_user',
        'id_aset',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lapor' => 'datetime',
        ];
    }
}
