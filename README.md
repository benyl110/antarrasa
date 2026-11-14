# AntarRasa

AntarRasa is a responsive Laravel application for local food businesses and customers. The MVP has exactly two account roles: `mitra` and `konsumen`. There is no Owner or Driver login, and fresh databases are not seeded with sample records. For this local switch, existing SQLite accounts and store data were copied to Laragon MySQL; the original SQLite file remains untouched.

## Local setup

1. Install PHP 8.3+, Composer, and MySQL. Create an empty database named `antarasa_db` if it does not already exist. In the current Laragon installation, this database is already configured and ready.
2. From the project folder in PowerShell, install dependencies and prepare the environment:

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Configure the database values in `.env` for the local MySQL instance:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=antarasa_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. Run the migrations, publish the public upload link, and start Laravel:

   ```powershell
   php artisan migrate
   php artisan storage:link
   php artisan serve
   ```

   The Blade views and public CSS/JavaScript do not require npm or a frontend build.

## Account onboarding

- Customers register at `/daftar/konsumen` and can use the account immediately.
- Mitra applications are created as pending and cannot sign in until activated by an operator:

  ```powershell
  php artisan antarasa:activate-mitra mitra@example.com
  ```

- An operator can provision an active Mitra account outside the user application. Omit `--password` to enter the initial password through the hidden terminal prompt:

  ```powershell
  php artisan antarasa:provision-mitra mitra@example.com --name="Nama Mitra" --username=mitra-lokal --phone=081234567890 --store="Nama Toko" --address="Alamat toko"
  ```

  The Mitra must change this temporary password after the first sign-in. Do not pass real passwords as command-line options, because shell history may retain them.

## MVP behavior

- A cart contains items from one store only. Pickup orders reserve stock during checkout; delivery requests reserve stock only after the customer approves the verified distance and total.
- Checkout requests use a unique token so a repeated submission cannot create a second order or reserve inventory twice.
- Delivery is limited to 5 km. The fixed fee is Rp5,000 up to 2 km and Rp8,000 above 2 km through 5 km. The store verifies road distance manually.
- Manual transfer is available only after the Mitra configures bank details in the store profile. Proof files are stored privately and accessed through an authenticated order route. COD is confirmed by the Mitra after completion.
- Products are archived rather than deleted, and order items retain name and price snapshots.

## Tests

```powershell
php artisan test
```

Tests use an in-memory SQLite database. Production and local deployments can use MySQL.
