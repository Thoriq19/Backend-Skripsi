<?php

namespace App\Console\Commands;

use App\Models\Sewa;
use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Shared\MicroserviceClient;

class GenerateTagihanBulanan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tagihan:generate-bulanan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tagihan bulanan otomatis untuk semua sewa aktif (Prosedur 6b)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📋 Generating tagihan bulanan...');

        $notifClient = new MicroserviceClient(
            env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
        );

        $bulanIni = Carbon::now()->format('Y-m');
        $sewaAktif = Sewa::where('status_sewa', 'aktif')->get();

        $generated = 0;
        $skipped = 0;

        foreach ($sewaAktif as $sewa) {
            // Cek apakah tagihan bulan ini sudah ada
            $exists = Tagihan::where('id_sewa', $sewa->id)
                ->where('bulan_tagihan', $bulanIni)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Hitung tanggal jatuh tempo: hari dari tanggal_masuk pada bulan ini
            $hariMasuk = Carbon::parse($sewa->tanggal_masuk)->day;
            $tanggalJatuhTempo = Carbon::now()->startOfMonth()->day($hariMasuk);

            // Jika hari masuk > hari terakhir bulan ini, gunakan hari terakhir
            if ($hariMasuk > Carbon::now()->daysInMonth) {
                $tanggalJatuhTempo = Carbon::now()->endOfMonth()->startOfDay();
            }

            // Gunakan harga_sewa yang terkunci saat deal
            $jumlahTagihan = $sewa->harga_sewa ?? 0;

            if ($jumlahTagihan <= 0) {
                $this->warn("  ⚠ Sewa ID {$sewa->id} tidak memiliki harga_sewa, dilewati.");
                $skipped++;
                continue;
            }

            $tagihan = Tagihan::create([
                'bulan_tagihan'      => $bulanIni,
                'tanggal_jatuhtempo' => $tanggalJatuhTempo->toDateString(),
                'jumlah_tagihan'     => $jumlahTagihan,
                'status_tagihan'     => 'belum_bayar',
                'id_sewa'            => $sewa->id,
            ]);

            // Kirim notifikasi ke penghuni
            $this->sendNotifikasi($notifClient, $sewa, $tagihan);

            $generated++;
            $this->line("  ✓ Tagihan dibuat untuk Sewa ID: {$sewa->id} (User ID: {$sewa->id_user})");
        }

        $this->info("✅ Selesai. Tagihan dibuat: {$generated}, Dilewati (sudah ada): {$skipped}");

        return Command::SUCCESS;
    }

    /**
     * Kirim notifikasi tagihan baru ke penghuni via Notification Service.
     */
    private function sendNotifikasi(MicroserviceClient $client, Sewa $sewa, Tagihan $tagihan): void
    {
        try {
            $client->post('/api/notifikasi', [
                'id_user'      => $sewa->id_user,
                'judul'        => 'Tagihan Baru',
                'pesan'        => "Tagihan bulan {$tagihan->bulan_tagihan} telah dibuat. Jumlah: Rp " . number_format($tagihan->jumlah_tagihan, 0, ',', '.') . ". Jatuh tempo: {$tagihan->tanggal_jatuhtempo}.",
                'tipe'         => 'pembayaran',
                'id_terkait'   => $tagihan->id,
                'tipe_terkait' => 'tagihan',
            ]);
        } catch (\Exception $e) {
            $this->error("  ✗ Gagal kirim notifikasi untuk user ID {$sewa->id_user}: {$e->getMessage()}");
        }
    }
}
