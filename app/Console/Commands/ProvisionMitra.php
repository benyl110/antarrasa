<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProvisionMitra extends Command
{
    protected $signature = 'antarasa:provision-mitra
        {email : Email akun mitra}
        {--name= : Nama penanggung jawab}
        {--username= : Username unik}
        {--phone= : Nomor telepon}
        {--store= : Nama toko}
        {--address= : Alamat toko}
        {--password= : Kata sandi sementara (bila kosong akan diminta secara tersembunyi)}';

    protected $description = 'Buat akun mitra aktif melalui prosedur provisioning internal';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) ($this->option('name') ?: $this->ask('Nama penanggung jawab'));
        $username = (string) ($this->option('username') ?: $this->ask('Username'));
        $phone = (string) ($this->option('phone') ?: $this->ask('Nomor telepon'));
        $storeName = (string) ($this->option('store') ?: $this->ask('Nama toko'));
        $address = (string) ($this->option('address') ?: $this->ask('Alamat toko'));
        $password = (string) ($this->option('password') ?: $this->secret('Kata sandi sementara'));

        try {
            validator(compact('email', 'name', 'username', 'phone', 'storeName', 'address', 'password'), [
                'email' => ['required', 'email', 'unique:users,email'],
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'alpha_dash', 'max:100', 'unique:users,username'],
                'phone' => ['required', 'string', 'regex:/^[0-9+()\\-\\s]{8,30}$/', 'unique:users,phone'],
                'storeName' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:2000'],
                'password' => ['required', 'string', 'min:8'],
            ])->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $errors) {
                foreach ($errors as $error) {
                    $this->error($error);
                }
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $name, $username, $phone, $storeName, $address, $password): void {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'phone' => $phone,
                'password' => Hash::make($password),
                'role' => 'mitra',
                'account_status' => 'active',
                'must_change_password' => true,
            ]);

            Store::create([
                'user_id' => $user->id,
                'store_name' => $storeName,
                'phone' => $phone,
                'address' => $address,
                'store_status' => 'active',
                'is_open' => false,
                'max_delivery_distance_km' => 5,
            ]);
        });

        $this->info('Akun mitra aktif berhasil dibuat. Berikan kredensial awal melalui kanal yang aman.');

        return self::SUCCESS;
    }
}
