# Modul 1 — Fasa 2: Enums & Eloquent Casts

**Projek:** PESGO — Personal Shopper and Group Order  
**Modul:** Modul 1 — Modul Pengurusan Pengguna (User Management Module)  
**Fasa:** Fasa 2 — Enums & Eloquent Casts  
**Status:** COMPLETED (SELESAI)  
**Rangka Kerja:** Laravel 12.68.0 | PHP 8.2.12 | MySQL 8.0.31 (`pesgo`)  
**Dokumen Rujukan Rasmi:** `Phase 2 - Enums & Eloquent Casts.md` (English)  
**Fasa Sebelumnya:** `Phase 1 - Eloquent Models & Relationships - BM.md`  

---

## 1. Tujuan

Tujuan utama pelaksanaan **Fasa 2** adalah untuk menetapkan **keselamatan jenis data yang ketat (*strict type safety*)** bagi status dan nilai domain diskret merentasi Model Eloquent dalam Modul 1.

Melalui penggunaan **PHP 8.2 String-Backed Enums** dan pendaftarannya ke dalam ciri *model casts* Laravel 12, aplikasi PESGO menyingkirkan penggunaan teks mentah rambang (*magic strings*) semasa berinteraksi dengan status akaun pengguna, status onboarding Personal Shopper, dan pengenal pasti peranan sistem. Apabila atribut-atribut ini dibaca daripada atau dimasukkan ke dalam model Eloquent, Laravel secara automatik menukarnya kepada objek Enum yang sah, sekali gus mengelakkan ralat ejaan dan data tidak sah semasa waktu pembangunan mahupun waktu operasi (*runtime*).

---

## 2. Skop Fasa 2

### Perkara yang Termasuk dalam Fasa 2
Fasa 2 merangkumi pembinaan PHP Backed Enums dan pemetaannya kepada model Eloquent sedia ada:
- **Enum `UserStatus`:** String-backed Enum bagi menjejak kitaran hayat akaun pengguna (`app/Enums/UserStatus.php`).
- **Enum `ShopperStatus`:** String-backed Enum bagi status verifikasi dan onboarding Personal Shopper (`app/Enums/ShopperStatus.php`).
- **Enum `RoleSlug`:** String-backed Enum bagi pengenal pasti peranan sistem (`app/Enums/RoleSlug.php`).
- **Penukaran Jenis Model Eloquent (*Eloquent Casts*):**
  - `User::$casts`: Menukar atribut `status` kepada `UserStatus`.
  - `UserProfile::$casts`: Menukar atribut `shopper_status` kepada `ShopperStatus`.
  - `Role::$casts`: Menukar atribut `slug` kepada `RoleSlug`.
- **Pengesahan Regresi Fasa 1:** Memastikan kesemua hubungan (*relationships*) dan trait `SoftDeletes` Fasa 1 kekal berfungsi sepenuhnya.

### Perkara yang BELUM Dibina (Di Luar Skop Fasa 2)
Komponen berikut **tidak dibina** dalam fasa ini dan ditangguhkan ke fasa seterusnya:
- Menambah logik perniagaan, peraturan validasi, atau kaedah servis ke dalam kelas Enum.
- Penyemaian data peranan pangkalan data (*RoleSeeder*) dan penyerahan peranan kepada pengguna (dikhaskan untuk Fasa 3).
- Kaedah pembantu peranan (*role helper methods*) pada model `User` seperti `hasRole()`, `isShopper()`, `isAdmin()` (dikhaskan untuk Fasa 3).
- Pengesahan API (Laravel Sanctum), pengurusan token, pendaftaran pengguna, dan log masuk.
- Lapisan perkhidmatan profil dan pengurusan alamat (*Services*).
- Aliran permohonan shopper dan proses semakan/kelulusan oleh Admin.
- Pengawal HTTP (*Controllers*), *Form Requests*, *Middleware*, dan *Routes*.
- Sebarang migrasi atau perubahan kepada skema pangkalan data fizikal.

---

## 3. Penjajaran Skema Database (Database Schema Alignment)

Nilai asas (*backing values*) bagi setiap Enum diselaraskan secara tepat 100% dengan jenis lajur fizikal dalam pangkalan data MySQL 8.0 `pesgo` tanpa sebarang perbezaan:

| Lajur Database | Jadual | Jenis Lajur di MySQL | Kelas Backed Enum | Nilai yang Diluluskan |
|---|---|---|---|---|
| `users.status` | `users` | `ENUM('active','pending','suspended','deactivated')` | `App\Enums\UserStatus` | `'active'`, `'pending'`, `'suspended'`, `'deactivated'` |
| `user_profiles.shopper_status` | `user_profiles` | `ENUM('none','pending','approved','rejected')` | `App\Enums\ShopperStatus` | `'none'`, `'pending'`, `'approved'`, `'rejected'` |
| `roles.slug` | `roles` | `VARCHAR(50)` | `App\Enums\RoleSlug` | `'customer'`, `'shopper'`, `'admin'` |

---

## 4. Pelaksanaan PHP Backed Enums (Kod Sumber Sebenar)

### 4.1. UserStatus (`app/Enums/UserStatus.php`)

Mentakrifkan kesemua status operasi yang sah bagi akaun pengguna PESGO.

```php
<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case DEACTIVATED = 'deactivated';
}
```

- **Lokasi Fail:** `app/Enums/UserStatus.php`
- **Jenis Asas (*Backing Type*):** `string`
- **Senarai Kes (*Cases*):**
  - `ACTIVE ('active')`: Akaun aktif normal yang mempunyai akses penuh kepada fungsi asas.
  - `PENDING ('pending')`: Akaun baharu didaftarkan yang sedang menunggu pengesahan emel/telefon.
  - `SUSPENDED ('suspended')`: Akaun yang digantung oleh pentadbir akibat pelanggaran syarat.
  - `DEACTIVATED ('deactivated')`: Akaun yang dinyahaktifkan atas permintaan pengguna sendiri.

### 4.2. ShopperStatus (`app/Enums/ShopperStatus.php`)

Mentakrifkan fasa kitaran hayat permohonan seorang pelanggan untuk menjadi Personal Shopper.

```php
<?php

namespace App\Enums;

enum ShopperStatus: string
{
    case NONE = 'none';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
```

- **Lokasi Fail:** `app/Enums/ShopperStatus.php`
- **Jenis Asas (*Backing Type*):** `string`
- **Senarai Kes (*Cases*):**
  - `NONE ('none')`: Status lalai; pengguna belum pernah memohon untuk menjadi Personal Shopper.
  - `PENDING ('pending')`: Permohonan telah dihantar bersama nombor pengenalan diri dan sedang menunggu semakan Admin.
  - `APPROVED ('approved')`: Permohonan diluluskan; pengguna disahkan sebagai Personal Shopper berdaftar.
  - `REJECTED ('rejected')`: Permohonan ditolak oleh Admin (pengguna boleh memohon semula selepas membetulkan dokumen).

### 4.3. RoleSlug (`app/Enums/RoleSlug.php`)

Mentakrifkan pengenal pasti sistem bagi peranan pengguna di bawah struktur RBAC PESGO.

```php
<?php

namespace App\Enums;

enum RoleSlug: string
{
    case CUSTOMER = 'customer';
    case SHOPPER = 'shopper';
    case ADMIN = 'admin';
}
```

- **Lokasi Fail:** `app/Enums/RoleSlug.php`
- **Jenis Asas (*Backing Type*):** `string`
- **Senarai Kes (*Cases*):**
  - `CUSTOMER ('customer')`: Peranan asas bagi setiap pengguna berdaftar (pembeli/pengguna biasa).
  - `SHOPPER ('shopper')`: Peranan Personal Shopper yang diberi kuasa menerima pesanan belian.
  - `ADMIN ('admin')`: Pentadbir sistem.
- **Nota Seni Bina:** Group Order **BUKAN** peranan sistem, maka ia sengaja tidak dimasukkan ke dalam Enum ini.

---

## 5. Pelaksanaan Eloquent Casts pada Model (Kod Sumber Sebenar)

Setiap model dikemas kini untuk memetakan lajur berkaitan menggunakan kaedah `casts()` bawaan Laravel 12. Kesemua konfigurasi dan hubungan daripada Fasa 1 dikekalkan sepenuhnya.

### 5.1. Model User (`app/Models/User.php`)

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('created_at');
    }
}
```

### 5.2. Model UserProfile (`app/Models/UserProfile.php`)

```php
<?php

namespace App\Models;

use App\Enums\ShopperStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    /** @use HasFactory<\Database\Factories\UserProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'full_name',
        'phone_number',
        'phone_verified_at',
        'identification_no',
        'avatar_url',
        'bio',
        'shopper_status',
        'shopper_verified_at',
    ];

    protected $hidden = [
        'identification_no',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'shopper_verified_at' => 'datetime',
            'shopper_status' => ShopperStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

### 5.3. Model Role (`app/Models/Role.php`)

```php
<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slug' => RoleSlug::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')
            ->withPivot('created_at');
    }
}
```

---

## 6. Faedah Keselamatan Jenis & Pengalaman Pembangun (Type Safety Benefits)

1. **Penyingkiran Teks Ralat (*Magic Strings*):** Pembangun berinteraksi menggunakan pemalar seperti `UserStatus::ACTIVE` berbanding teks mentah `'active'`, menghapuskan risiko kesilapan ejaan (contoh: `'activ'`, `'Active'`).
2. **Bantuan Autolengkap IDE (*Autocompletion*):** Editor kod (seperti VS Code / PhpStorm) menyediakan cadangan autolengkap segera bagi setiap kes Enum yang sah.
3. **Penukaran Automatik Bersiri (*Serialization / Deserialization*):**
   * Semasa Menulis: Menetapkan `$user->status = UserStatus::ACTIVE` membolehkan Eloquent menyimpan nilai teks mentah `'active'` ke dalam MySQL secara telus.
   * Semasa Membaca: Mendapatkan nilai `$user->status` secara automatik mengembalikan objek `App\Enums\UserStatus`.
4. **Petunjuk Jenis (*Type-Hinting*) dalam Servis:** Kaedah perkhidmatan domain pada masa hadapan boleh menggunakan petunjuk jenis yang ketat (contoh: `public function kemasKiniStatus(User $user, UserStatus $status): void`), mengesan sebarang ketidakpadanan jenis data sebelum kod dilaksanakan.

---

## 7. Pengesahan Regresi & Keserasian Fasa 1

Sepanjang pelaksanaan Fasa 2, kesemua keputusan seni bina dan kod daripada Fasa 1 disahkan kekal utuh tanpa sebarang gangguan:

| Model | Relationship Dikekalkan | SoftDeletes Dikekalkan | Atribut `$fillable` Dikekalkan |
|---|---|---|---|
| **`User`** | `profile()` (HasOne), `addresses()` (HasMany), `roles()` (BelongsToMany) | Ya (`SoftDeletes`) | Ya (`name`, `email`, `password`, `status`) |
| **`UserProfile`** | `user()` (BelongsTo) | Ya (`SoftDeletes`) | Ya (`user_id`, `full_name`, `phone_number`, `identification_no`, dsb.) |
| **`UserAddress`** | `user()` (BelongsTo) | Ya (`SoftDeletes`) | Ya (`user_id`, `recipient_name`, `latitude`, `longitude`, dsb.) |
| **`Role`** | `users()` (BelongsToMany) | Tidak (Mengikut skema fizikal database) | Ya (`name`, `slug`, `description`) |

---

## 8. Pengesahan yang Dilaksanakan (Verification)

Pemeriksaan selamat dan baca-sahaja (*read-only*) berikut telah disempurnakan:

1. **Ujian Sintaks PHP CLI (`php -l`):**
   * Kesemua fail Enum dan fail Model disemak dan disahkan bebas daripada sebarang ralat sintaks (*No syntax errors detected*).
2. **Pemeriksaan Nilai Enum (Tinker):**
   * `UserStatus::cases()` disahkan mengembalikan tepat 4 nilai string (`active`, `pending`, `suspended`, `deactivated`).
   * `ShopperStatus::cases()` disahkan mengembalikan tepat 4 nilai string (`none`, `pending`, `approved`, `rejected`).
   * `RoleSlug::cases()` disahkan mengembalikan tepat 3 nilai string (`customer`, `shopper`, `admin`).
3. **Ujian Penukaran Jenis Dalam Memori (Tinker):**
   * Ujian memasukkan nilai string ke dalam atribut membuktikan ia menghasilkan objek Enum yang sah dalam memori PHP tanpa perlu menyimpan ke database.
4. **Pemeriksaan Integriti Pangkalan Data:**
   * Bilangan baris data kekal **0** merentasi semua jadual (`users`: 0, `user_profiles`: 0, `user_addresses`: 0, `roles`: 0, `role_user`: 0).

---

## 9. Keselamatan Database (Database Safety)

- **Tiada Perubahan Skema:** Tiada jadual, lajur, atau kekangan fizikal MySQL `pesgo` yang diubah.
- **Tiada Pelaksanaan Migrasi:** Arahan migrasi tidak dijalankan.
- **Tiada Rekod Ujian Disimpan:** Ujian casting dijalankan secara simulasi dalam memori tanpa menyentuh pangkalan data.

---

## 10. Status Semasa

```text
Modul 1 — Modul Pengurusan Pengguna
Fasa 2 — Enums & Eloquent Casts
Status: COMPLETED (SELESAI)
```

Fasa 3 **BELUM** dilaksanakan pada ketika ini.

---

## 11. Fasa Seterusnya

Fasa berikutnya yang dirancang bagi Modul 1 adalah:

> **Phase 3 — Role Foundation & Database Seeding**  
> Membina `RoleSeeder` untuk menyemai 3 rekod peranan teras (`customer`, `shopper`, `admin`) ke dalam jadual fizikal `roles` menggunakan nilai `RoleSlug`, dan membina kaedah pembantu peranan (*role helper methods*) pada model `User`.
