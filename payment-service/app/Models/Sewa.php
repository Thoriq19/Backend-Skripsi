<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sewa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sewa';

    protected $fillable = [
        'tanggal_masuk',
        'tanggal_keluar',
        'status_sewa',
        'harga_sewa',
        'id_user',
        'id_kamar',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk'  => 'date',
            'tanggal_keluar' => 'date',
            'harga_sewa'     => 'decimal:2',
        ];
    }

    /**
     * Get the tagihan for this sewa.
     */
    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'id_sewa');
    }

    /**
     * Get the user for this sewa.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
