<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    use HasFactory;

    protected $table = 'tagihan';

    protected $fillable = [
        'bulan_tagihan',
        'tanggal_jatuhtempo',
        'jumlah_tagihan',
        'status_tagihan',
        'id_sewa',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_jatuhtempo' => 'date',
            'jumlah_tagihan'     => 'decimal:2',
        ];
    }

    /**
     * Get the sewa that owns this tagihan.
     */
    public function sewa()
    {
        return $this->belongsTo(Sewa::class, 'id_sewa');
    }

    /**
     * Get the pembayaran for this tagihan.
     */
    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'id_tagihan');
    }
}
