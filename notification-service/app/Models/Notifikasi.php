<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    protected $fillable = [
        'id_user',
        'judul',
        'pesan',
        'tipe',
        'dibaca',
        'id_terkait',
        'tipe_terkait',
    ];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
        ];
    }
}
