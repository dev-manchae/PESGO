# PESGO — Modul 1 — Fasa 3
## Asas Peranan & Penyemaian Pangkalan Data (Role Foundation & Database Seeding)

**Projek:** PESGO — Personal Shopper and Group Order  
**Modul:** Modul 1 — Modul Pengurusan Pengguna (User Management Module)  
**Fasa:** Fasa 3 — Asas Peranan & Penyemaian Pangkalan Data (Role Foundation & Database Seeding)  
**Status:** SELESAI (COMPLETED)  
**Rangka Kerja:** Laravel 12.68.0 | PHP 8.2.12 | MySQL 8.0.31 (`pesgo`)  
**Fasa Terdahulu:** `Phase 2 - Enums & Eloquent Casts - BM.md`  

---

## 1. Gambaran Keseluruhan Fasa (Phase Overview)

Fasa 3 mewujudkan **Asas Kawalan Akses Berasaskan Peranan (RBAC)** dan **Lapisan Penyemaian Pangkalan Data (Database Seeding)** bagi Modul 1 dalam aplikasi PESGO.

Dibina terus di atas model Eloquent yang telah dimuktamadkan dalam Fasa 1 dan string-backed enum PHP 8.2 dalam Fasa 2, Fasa 3 membenihkan peranan sistem kanonikal PESGO ke dalam pangkalan data fizikal dan melengkapkan model `User` dengan kaedah pembantu (*helpers*) yang ekspresif, selamat dari segi jenis data (*type-safe*), serta peka terhadap pengurusan cache memori bagi semakan, pemberian, dan penyingkiran peranan.

---

## 2. Objektif (Objectives)

1. **Menyemai Peranan Kanonikal Sistem:** Memasukkan tiga peranan domain teras (`admin`, `shopper`, `customer`) ke dalam jadual fizikal `roles` menggunakan seeder yang bersifat *idempotent*.
2. **Pembersihan Database Seeder Induk:** Mengintegrasikan `RoleSeeder` ke dalam `DatabaseSeeder` sambil menghapuskan kod pengguna palsu (*dummy factory users*) bawaan Laravel.
3. **Membina Kaedah Pembantu RBAC Eloquent:** Menambah kaedah kemudahan pada `User.php` yang menyokong keupayaan dwi-persona (`customer` dan `shopper` secara serentak).
4. **Menguatkuasakan Integriti Jenis Data & Cache:** Menyokong penggunaan enum dan string secara saling bertukar ganti, menghalang ralat perlanggaran kunci unik pangkalan data, dan membersihkan cache koleksi memori lapuk apabila peranan diubah suai.
5. **Memelihara Sempadan Skop Kerja:** Mengekalkan pengasingan ketat daripada fasa-fasa seterusnya (tiada token Sanctum, tiada endpoint autentikasi, tiada logik Group Order).

---

## 3. Skop Kerja (Scope)

### Dalam Skop Fasa 3
* **`RoleSeeder` (`database/seeders/RoleSeeder.php`):** Seeder pangkalan data idempotent yang membina 3 peranan kanonikal menggunakan `RoleSlug`.
* **`DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`):** Seeder induk yang memanggil `RoleSeeder` dan membuang penjanaan pengguna palsu.
* **Kaedah Pembantu RBAC `User` (`app/Models/User.php`):**
  * `hasRole(RoleSlug|string $role): bool`
  * `hasAnyRole(array $roles): bool`
  * `isAdmin(): bool`
  * `isShopper(): bool`
  * `isCustomer(): bool`
  * `assignRole(RoleSlug|string|Role $role): void`
  * `removeRole(RoleSlug|string|Role $role): void`
* **Pengesahan & Ujian:** Ujian tanpa musnah (*non-destructive*) yang membuktikan penyemaian peranan, idempotensi, sokongan dwi-persona, pembersihan cache relasi, dan pangkalan data yang kekal bersih.

### Luar Skop / Ditangguhkan Secara Eksplisit
Fungsi-fungsi berikut **sengaja tidak dilaksanakan** dalam Fasa 3 dan ditangguhkan ke fasa seterusnya:
* **Autentikasi & Token (Fasa 4):** Pemasangan Laravel Sanctum (`php artisan install:api`), token akses peribadi, pengurusan sesi log masuk/keluar.
* **Servis Pendaftaran & Pengesahan (Fasa 5):** Aliran pendaftaran, Form Requests, `AuthService`, `ProfileService`, `AddressService`.
* **Lapisan HTTP (Fasa 6):** Controllers, laluan API (`routes/api.php`), API Resource responses.
* **Polisi Kebenaran & Middleware (Fasa 7):** Middleware peranan, Laravel Gates, dan Policies (`AddressPolicy`, `ProfilePolicy`).
* **Ujian Integrasi Automatik (Fasa 8):** Ujian integrasi ciri HTTP yang menyeluruh (*Feature Tests*).
* **Aliran Pengesahan Personal Shopper:** Muat naik dokumen, verifikasi kad pengenalan, dan kelulusan pentadbir (diuruskan dalam domain profil).
* **Fungsi Pesanan Kumpulan (Group Order):** Katalog pesanan kumpulan, kolam pesanan (*order pools*), dan bayaran berasingan (diuruskan sepenuhnya dalam Modul 2).

---

## 4. Seni Bina Peranan (Role Architecture)

PESGO melaksanakan struktur Many-to-Many RBAC normal merentasi tiga jadual:

```text
+-----------------------+              +-----------------------+              +-----------------------+
|         users         |              |       role_user       |              |         roles         |
+-----------------------+              +-----------------------+              +-----------------------+
| id (PK)               |<------------>| user_id (PK, FK)      |<------------>| id (PK)               |
| name                  |              | role_id (PK, FK)      |              | name (UK)             |
| email (UK)            |              | created_at            |              | slug (UK)             |
| password              |              +-----------------------+              | description           |
| status (Enum)         |                                                     | created_at            |
| deleted_at            |                                                     | updated_at            |
+-----------------------+                                                     +-----------------------+
```

### Prinsip Seni Bina Utama:
1. **1 Individu Sebenar = 1 Akaun PESGO:** Pengguna hanya mempunyai satu rekod induk di jadual `users`.
2. **Sokongan Dwi-Persona (Dual Persona):** Pengguna boleh bertindak sebagai **Customer** (pembeli) dan **Personal Shopper** (penghantar/runner) serentak tanpa perlu mendaftar akaun berasingan. Ini disokong oleh kunci primer komposit `(user_id, role_id)` pada jadual `role_user`.
3. **Group Order Bukan Peranan Sistem:** Penyertaan dalam Group Order adalah transaksi/aktiviti dalam Modul 2, bukannya peranan kebenaran sistem. Tiada peranan `group_order`.

---

## 5. Enum RoleSlug

Pengecam rasmi bagi peranan sistem PESGO diuruskan melalui string-backed enum `App\Enums\RoleSlug`:

```php
namespace App\Enums;

enum RoleSlug: string
{
    case CUSTOMER = 'customer';
    case SHOPPER = 'shopper';
    case ADMIN = 'admin';
}
```

* **Lokasi Fail:** `app/Enums/RoleSlug.php`
* **Jenis Backing:** `string`
* **Kes Enum:**
  * `CUSTOMER ('customer')`: Peranan pembeli asas; aktif bagi semua pengguna berdaftar.
  * `SHOPPER ('shopper')`: Persona Personal Shopper yang disahkan dengan keupayaan runner.
  * `ADMIN ('admin')`: Pentadbir platform dengan kuasa pemantauan sistem.

---

## 6. Integrasi Model Role

Model `Role` (`app/Models/Role.php`) dipetakan terus ke jadual `roles`:

```php
namespace App\Models;

use App\Enums\RoleSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

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

> [!NOTE]
> **Tiada SoftDeletes pada Role:** Selaras dengan skema fizikal pangkalan data, jadual `roles` tidak mempunyai lajur `deleted_at`. Model `Role.php` sengaja tidak memasukkan trait `SoftDeletes` bagi mengekalkan ketepatan dengan skema fizikal.

---

## 7. RoleSeeder

Kelas `RoleSeeder` (`database/seeders/RoleSeeder.php`) menyemai tiga peranan kanonikal secara *idempotent*:

```php
namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'slug' => RoleSlug::ADMIN,
                'name' => 'Administrator',
                'description' => 'Pengurus sistem PESGO dan pemantau transaksi',
            ],
            [
                'slug' => RoleSlug::SHOPPER,
                'name' => 'Personal Shopper',
                'description' => 'Penyedia servis belian peribadi dan pesanan kumpulan',
            ],
            [
                'slug' => RoleSlug::CUSTOMER,
                'name' => 'Customer',
                'description' => 'Pengguna pembeli biasa dan penyertai pesanan kumpulan',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
```

### Keputusan Reka Bentuk Seni Bina:
* **`updateOrCreate()`:** Menggunakan padanan pada `['slug' => $role['slug']]`. Sekiranya peranan wujud, nama dan penerangannya dikemas kini; jika tiada, ia dimasukkan. Ini mengelakkan ralat `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry` pada indeks unik `slug` dan `name`.
* **Selamat Dijalankan Berulang Kali:** Seeder boleh dijalankan bila-bila masa semasa persediaan awal, skrip ujian automatik, mahupun saluran integrasi berterusan (*CI/CD*).

---

## 8. DatabaseSeeder

Fail induk `DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`) mengarahkan pelaksanaan kepada `RoleSeeder`:

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);
    }
}
```

### Perubahan Utama yang Dibuat:
* Membuang kod bawaan Laravel yang memanggil `User::factory()->create(...)`.
* Menjalankan arahan `php artisan db:seed` kini hanya membina tiga peranan rasmi dan membiarkan jadual `users` bersih sepenuhnya (0 baris data).

---

## 9. Kaedah Pembantu RBAC Pengguna (User RBAC Helpers)

Tujuh kaedah pembantu telah dilaksanakan secara langsung pada `App\Models\User`:

```php
/**
 * Check if the user has a specific role.
 */
public function hasRole(RoleSlug|string $role): bool
{
    $roleSlug = $role instanceof RoleSlug ? $role : RoleSlug::tryFrom($role);

    if ($roleSlug === null) {
        return false;
    }

    if ($this->relationLoaded('roles')) {
        return $this->roles->contains(function (Role $r) use ($roleSlug) {
            return $r->slug === $roleSlug || $r->slug?->value === $roleSlug->value;
        });
    }

    return $this->roles()->where('slug', $roleSlug->value)->exists();
}

/**
 * Check if the user has any of the given roles.
 *
 * @param  array<RoleSlug|string>  $roles
 */
public function hasAnyRole(array $roles): bool
{
    foreach ($roles as $role) {
        if ($this->hasRole($role)) {
            return true;
        }
    }

    return false;
}

/**
 * Check if the user is an Administrator.
 */
public function isAdmin(): bool
{
    return $this->hasRole(RoleSlug::ADMIN);
}

/**
 * Check if the user is a Personal Shopper.
 */
public function isShopper(): bool
{
    return $this->hasRole(RoleSlug::SHOPPER);
}

/**
 * Check if the user is a Customer.
 */
public function isCustomer(): bool
{
    return $this->hasRole(RoleSlug::CUSTOMER);
}
```

---

## 10. Pemberian & Penyingkiran Peranan (Role Assignment and Removal)

Kaedah mutasi peranan menjamin keselamatan pangkalan data dan konsistensi memori:

```php
/**
 * Assign a role to the user idempotently.
 */
public function assignRole(RoleSlug|string|Role $role): void
{
    $roleModel = $role instanceof Role
        ? $role
        : Role::where('slug', ($role instanceof RoleSlug ? $role->value : $role))->first();

    if (! $roleModel) {
        $identifier = $role instanceof RoleSlug ? $role->value : (string) $role;
        throw new InvalidArgumentException("Role [{$identifier}] does not exist.");
    }

    $this->roles()->syncWithoutDetaching([$roleModel->id]);
    $this->unsetRelation('roles');
}

/**
 * Remove a role from the user.
 */
public function removeRole(RoleSlug|string|Role $role): void
{
    $roleModel = $role instanceof Role
        ? $role
        : Role::where('slug', ($role instanceof RoleSlug ? $role->value : $role))->first();

    if ($roleModel) {
        $this->roles()->detach($roleModel->id);
        $this->unsetRelation('roles');
    }
}
```

### Butiran Kejuruteraan Kritikal:
1. **Penggunaan `syncWithoutDetaching()` Menggantikan `attach()`:** Memandangkan `role_user` mempunyai kunci primer komposit `(user_id, role_id)`, penggunaan `attach()` pada peranan yang telah dimiliki akan menyebabkan ralat maut MySQL `1062 Duplicate entry`. Kaedah `syncWithoutDetaching()` memastikan operasi pemberian peranan adalah idempotent tanpa ralat.
2. **`detach()` Semasa Penyingkiran:** Memadam rekod pivot secara selamat. Jika pengguna memang tidak memiliki peranan tersebut, operasi selesai tanpa sebarang ralat.

---

## 11. Sokongan Dwi-Persona (Dual Persona Support)

Dalam model perniagaan PESGO, pengguna boleh bertindak sebagai pembeli dan Personal Shopper serentak. Kod ini menjamin sokongan tersebut:

```php
$user->assignRole(RoleSlug::CUSTOMER);
$user->assignRole(RoleSlug::SHOPPER);

$user->isCustomer(); // Mengembalikan: true
$user->isShopper();  // Mengembalikan: true
```

* Kaedah `isCustomer()` dan `isShopper()` tidak menggunakan logik saling eksklusif (`if/else`).
* Menyingkirkan peranan shopper (`$user->removeRole(RoleSlug::SHOPPER)`) menetapkan `isShopper()` kepada `false`, manakala `isCustomer()` kekal bernilai `true`.

---

## 12. Pengurusan Relasi & Cache Memori (Relationship Loading & Cache Handling)

Bagi mengelakkan **Masalah Query N+1** dan **Ralat Cache Memori Lapuk (*Stale Cache*)**, `User.php` mengaplikasikan semakan dwi-mod dan pembersihan cache relasi:

### A. Penilaian Dwi-Mod dalam `hasRole()`
* **Sekiranya Relasi `roles` Telah Dimuatkan (`$this->relationLoaded('roles')`):**  
  `hasRole()` menilai koleksi Eloquent dalam memori menggunakan `$this->roles->contains(...)`. Tiada query SQL baharu dijalankan.
* **Sekiranya Relasi `roles` Belum Dimuatkan:**  
  `hasRole()` menjalankan query SQL pantas berindeks:  
  `$this->roles()->where('slug', $roleSlug->value)->exists();`

### B. Pembersihan Cache Memori via `unsetRelation('roles')`
Apabila `assignRole()` atau `removeRole()` mengubah rekod dalam pangkalan data, pemanggilan `$this->unsetRelation('roles')` memadamkan cache koleksi lapuk pada objek model. Panggilan seterusnya kepada `$user->roles` atau `hasRole()` akan mengambil data terkini daripada pangkalan data.

---

## 13. Pengendalian Input Peranan Tidak Sah (Invalid Role Handling)

Aplikasi membezakan secara jelas antara pertanyaan (*query*) dan pengubahsuaian (*mutation*):

| Kaedah | Jenis Input | Tingkah Laku | Justifikasi |
|---|---|---|---|
| `hasRole()` | String tidak dikenali (cth: `'superadmin'`, `'group_order'`) | Mengembalikan `false` secara selamat | Pengguna tidak boleh memegang peranan yang tidak wujud; query mengembalikan boolean tanpa mencetuskan ralat. |
| `assignRole()` | String peranan tidak wujud atau enum tidak sah | Melontarkan `InvalidArgumentException` | Menghalang kerosakan data atau cubaan memasukkan nilai kunci asing kosong/rosak. |
| `removeRole()` | Peranan yang tidak dimiliki oleh pengguna | Selesai secara selamat tanpa ralat | Detachment bersifat idempotent; tiada operasi dijalankan jika relasi tiada. |

---

## 14. Pengesahan Pangkalan Data Fizikal (Database Verification)

Pemeriksaan terus ke atas pangkalan data MySQL `pesgo` mengesahkan:

```sql
SELECT id, slug, name, description FROM pesgo.roles ORDER BY id;
```

| `id` | `slug` | `name` | `description` | Cast Backed Enum |
|:---:|:---|:---|:---|:---|
| **1** | `admin` | Administrator | Pengurus sistem PESGO dan pemantau transaksi | `RoleSlug::ADMIN` |
| **2** | `shopper` | Personal Shopper | Penyedia servis belian peribadi dan pesanan kumpulan | `RoleSlug::SHOPPER` |
| **3** | `customer` | Customer | Pengguna pembeli biasa dan penyertai pesanan kumpulan | `RoleSlug::CUSTOMER` |

### Kiraan Status Pangkalan Data:
* **Jadual `roles`:** Tepat **3** rekod kanonikal.
* **Semakan `group_order`:** **0** rekod sepadan dengan `slug LIKE '%group%'`.
* **Jadual `role_user`:** **0** baris data (bersih).
* **Jadual `users`:** **0** baris data (bersih).
* **Jadual `migrations`:** **7** rekod migrasi (semua Batch 1).

---

## 15. Ujian & Pengesahan yang Dilaksanakan (Verification Tests)

Semua prosedur pengesahan dijalankan secara selamat tanpa mengubah skema atau meninggalkan data ujian:

1. **Semakan Sintaks PHP CLI (`php -l`):**
   * `backend/app/Models/User.php`: Lulus (0 ralat).
   * `backend/database/seeders/RoleSeeder.php`: Lulus (0 ralat).
   * `backend/database/seeders/DatabaseSeeder.php`: Lulus (0 ralat).
2. **Pelaksanaan Seeder & Idempotensi:**
   * Arahan `php artisan db:seed --class=RoleSeeder` berjaya (0 ralat).
   * Arahan `php artisan db:seed` berjaya (0 ralat). Jumlah peranan kekal 3.
3. **Ujian Transaksi Automatik (`DB::beginTransaction()` / `DB::rollBack()`):**
   * Mengesahkan status awal: `isCustomer()`, `isShopper()`, `isAdmin()` semuanya `false`.
   * Mengesahkan `assignRole(RoleSlug::CUSTOMER)` menetapkan `isCustomer()` kepada `true`.
   * Mengesahkan `assignRole(RoleSlug::SHOPPER)` seterusnya menetapkan `isShopper()` kepada `true` dan `isCustomer()` kekal `true`.
   * Mengesahkan `hasRole('customer')` (string) dan `hasRole(RoleSlug::CUSTOMER)` (enum) memulangkan hasil yang sama.
   * Mengesahkan semakan koleksi memori apabila `$user->load('roles')` aktif.
   * Mengesahkan panggilan `assignRole()` berganda tidak menyebabkan ralat MySQL 1062.
   * Mengesahkan fungsi `hasAnyRole()`.
   * Mengesahkan `hasRole('non_existent')` memulangkan `false`.
   * Mengesahkan `removeRole(RoleSlug::SHOPPER)` membuang persona shopper tetapi mengekalkan peranan customer.
   * Mengesahkan `assignRole(RoleSlug::ADMIN)` dan `removeRole('admin')`.
   * Transaksi diundur balik (*rolled back*): baris data `users` disahkan kekal 0.

---

## 16. Pertimbangan Keselamatan & Integriti (Security & Integrity)

1. **Perlindungan Mass-Assignment:** Peranan hanya boleh diberikan melalui relasi `roles()`; `$roles` tiada dalam `$fillable`, menghalang cubaan suntikan parameter melalui payload permintaan HTTP.
2. **Kekangan Kunci Asing CASCADE:** Kekangan pangkalan data `ON DELETE CASCADE ON UPDATE CASCADE` memastikan jika pengguna dipadam secara fizikal, rekod pivot `role_user` dipadamkan secara automatik.
3. **Tiada Kredensial Pentadbir Hardcoded:** `RoleSeeder` hanya membenihkan definisi peranan, bukannya akaun pengguna pentadbir dengan kata laluan terdedah.
4. **Pemisahan Status Shopper:** Peranan kebenaran `shopper` dalam `role_user` dipisahkan daripada status kitaran permohonan `user_profiles.shopper_status`. Kaedah pembantu `isShopper()` hanya memeriksa jadual RBAC, memastikan logik kebenaran kekal bersih.

---

## 17. Perkara yang Ditangguhkan (Deferred Functionality)

Bagi mengekalkan sempadan fasa pembangunan, fungsi berikut ditangguhkan:

* **Token API Laravel Sanctum:** Dijadualkan untuk Fasa 4.
* **Aliran Autentikasi (Daftar, Log Masuk, Lupa Kata Laluan):** Dijadualkan untuk Fasa 4 & Fasa 5.
* **Polisi Kebenaran & Middleware Laluan:** Dijadualkan untuk Fasa 7.
* **Fungsi Pesanan Kumpulan (Group Order):** Dikhaskan untuk Modul 2.

---

## 18. Fail yang Diubah (Files Changed)

Hanya tiga fail kod yang dicipta atau diubah suai bagi Fasa 3:

| Fail | Jenis Perubahan | Penerangan |
|---|:---:|---|
| [`backend/database/seeders/RoleSeeder.php`](file:///d:/PESGO/backend/database/seeders/RoleSeeder.php) | **BAHARU** | Seeder idempotent yang menyemai peranan `admin`, `shopper`, dan `customer`. |
| [`backend/database/seeders/DatabaseSeeder.php`](file:///d:/PESGO/backend/database/seeders/DatabaseSeeder.php) | **DIUBAH SUAI** | Menghubungkan `$this->call([RoleSeeder::class]);` dan memadam factory pengguna demo. |
| [`backend/app/Models/User.php`](file:///d:/PESGO/backend/app/Models/User.php) | **DIUBAH SUAI** | Menambah 7 kaedah pembantu RBAC dengan sokongan dwi-persona dan pembersihan cache. |

---

## 19. Status Akhir Fasa (Final Phase Status)

```text
Modul 1 — Modul Pengurusan Pengguna
Fasa 3 — Asas Peranan & Penyemaian Pangkalan Data
Status: SELESAI (COMPLETED - Diluluskan Audit)
```

Asas peranan RBAC kini telah beroperasi sepenuhnya, disahkan, dan didokumenkan. Sistem bersedia untuk beralih ke **Fasa 4: Asas Pengesahan API & Konfigurasi Laravel Sanctum**.
