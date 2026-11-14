<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActivateMitra extends Command
{
    protected $signature = 'antarasa:activate-mitra {email : Email pengajuan mitra}';

    protected $description = 'Aktifkan pengajuan akun mitra melalui prosedur operasional internal';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))
            ->where('role', 'mitra')
            ->where('account_status', 'pending')
            ->first();

        if (! $user) {
            $this->error('Pengajuan mitra pending dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user): void {
            $user->update(['account_status' => 'active']);
            $user->store()->update(['store_status' => 'active']);
        });

        $this->info('Akun mitra berhasil diaktifkan.');

        return self::SUCCESS;
    }
}
