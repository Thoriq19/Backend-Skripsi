<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'tanggal_bayar',
        'metode_pembayaran',
        'jumlah_bayar',
        'status_pembayaran',
        'payment_gateway',
        'external_id',
        'snap_token',
        'status_webhook',
        'id_tagihan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'datetime',
            'jumlah_bayar'  => 'decimal:2',
        ];
    }

    /**
     * Get the tagihan that this pembayaran belongs to.
     */
    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'id_tagihan');
    }
}
