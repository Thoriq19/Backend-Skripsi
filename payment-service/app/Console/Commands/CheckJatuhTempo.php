<?php

namespace App\Console\Commands;

use App\Models\Tagihan;
use Illuminate\Console\Command;
use Shared\MicroserviceClient;

class CheckJatuhTempo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tagihan:check-jatuhtempo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek tagihan yang mendekati jatuh tempo dan kirim notifikasi otomatis (H-8, H-5, H-0, lewat jatuh tempo)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking tagihan jatuh tempo...');

        $notifClient = new MicroserviceClient(
            env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
        );

        $tagihan = Tagihan::where('status_tagihan', 'belum_bayar')
            ->with('sewa')
            ->get();

        $notifCount = 0;
        $terlambatCount = 0;

        foreach ($tagihan as $item) {
            $selisih = now()->startOfDay()->diffInDays($item->tanggal_jatuhtempo, false);

            // H-8: 8 hari sebelum jatuh tempo
            if ($selisih == 8) {
                $this->sendNotifikasi($notifClient, $item, '8 hari lagi jatuh tempo', 'peringatan');
                $notifCount++;
            }

            // H-5: 5 hari sebelum jatuh tempo
            if ($selisih == 5) {
                $this->sendNotifikasi($notifClient, $item, '5 hari lagi jatuh tempo', 'peringatan');
                $notifCount++;
            }

            // H-0: Hari jatuh tempo
            if ($selisih == 0) {
                $this->sendNotifikasi($notifClient, $item, 'Hari ini jatuh tempo!', 'pembayaran');
                $notifCount++;
            }

            // Lewat jatuh tempo → update status
            if ($selisih < 0) {
                $item->update(['status_tagihan' => 'terlambat']);
                $this->sendNotifikasi($notifClient, $item, 'Tagihan sudah melewati jatuh tempo!', 'pembayaran');
                $terlambatCount++;
                $notifCount++;
            }
        }

        $this->info("✅ Selesai. Notifikasi terkirim: {$notifCount}, Tagihan terlambat: {$terlambatCount}");

        return Command::SUCCESS;
    }

    /**
     * Kirim notifikasi ke Notification Service via HTTP.
     */
    private function sendNotifikasi(MicroserviceClient $client, Tagihan $tagihan, string $pesan, string $tipe): void
    {
        try {
            $client->post('/api/notifikasi', [
                'id_user'      => $tagihan->sewa->id_user,
                'judul'        => 'Pengingat Pembayaran',
                'pesan'        => "Tagihan bulan {$tagihan->bulan_tagihan} - {$pesan}. Jumlah: Rp " . number_format($tagihan->jumlah_tagihan, 0, ',', '.'),
                'tipe'         => $tipe,
                'id_terkait'   => $tagihan->id,
                'tipe_terkait' => 'tagihan',
            ]);

            $this->line("  → Notifikasi terkirim untuk user ID: {$tagihan->sewa->id_user} ({$pesan})");
        } catch (\Exception $e) {
            $this->error("  ✗ Gagal kirim notifikasi: {$e->getMessage()}");
        }
    }
}
