<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kos extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kos';

    protected $fillable = [
        'nama_kos',
        'alamat_kos',
        'id_user',
    ];

    /**
     * Get the owner of this kos.
     */
    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'id_user');
    }

    /**
     * Get the kamar (rooms) in this kos.
     */
    public function kamar()
    {
        return $this->hasMany(Kamar::class, 'id_kos');
    }

    /**
     * Get the aset (assets) in this kos.
     */
    public function aset()
    {
        return $this->hasMany(Aset::class, 'id_kos');
    }
}
