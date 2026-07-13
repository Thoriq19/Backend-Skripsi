<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler: Check Jatuh Tempo
|--------------------------------------------------------------------------
|
| Menjalankan pengecekan tagihan jatuh tempo setiap hari jam 08:00.
| Akan mengirim notifikasi otomatis ke penghuni yang memiliki tagihan:
|   - H-8: 8 hari sebelum jatuh tempo
|   - H-5: 5 hari sebelum jatuh tempo
|   - H-0: Hari jatuh tempo
|   - Lewat jatuh tempo: Update status ke 'terlambat'
|
*/
Schedule::command('tagihan:check-jatuhtempo')->dailyAt('08:00');

/*
|--------------------------------------------------------------------------
| Scheduler: Generate Tagihan Bulanan
|--------------------------------------------------------------------------
|
| Menjalankan pembuatan tagihan otomatis setiap tanggal 1 setiap bulan.
| Akan membuat tagihan baru untuk setiap sewa aktif yang belum memiliki
| tagihan pada bulan tersebut (Prosedur 6b).
|
*/
Schedule::command('tagihan:generate-bulanan')->monthlyOn(1, '00:01');
