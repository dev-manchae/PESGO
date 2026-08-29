# PESGO - Analisis Pangkalan Data & Seni Bina Modul 1
**Modul 1: Modul Pengurusan Pengguna (User Management Module)**  
**Status Dokumen:** Fasa Reka Bentuk & Analisis Seni Bina (Architecture & Design Phase)  
**Tarikh Analisis:** 27 Ogos 2026  
**Persekitaran:** Laravel 12.68.0 | PHP 8.2.12 | MySQL 8.0.31 (Host: `127.0.0.1:3306`, DB: `pesgo`)

---

## Ringkasan Eksekutif (Executive Summary)

Penyelidikan mendalam terhadap pangkalan data MySQL melalui sambungan rasmi Laravel telah membongkar penemuan teknikal yang sangat kritikal:

1. **Status Sebenar Pangkalan Data `pesgo`:**
   - Pangkalan data `pesgo` pada pelayan MySQL 8.0 pada masa ini mengandungi **tepat 0 jadual (Empty / Clean Slate)**.
2. **Punca Angka 31 Jadual dalam `php artisan db:show`:**
   - Apabila perintah `php artisan db:show` dijalankan, enjin tatabahasa skema MySQL Laravel 12 (`Illuminate\Database\Schema\Grammars\MySqlGrammar::compileSchemaWhereClause()`) secara lalai tidak menyaring mengikut skema `table_schema = 'pesgo'` sekiranya pengguna MySQL mempunyai kebenaran global (`root`). Sebaliknya, ia mengecualikan skema sistem (`information_schema`, `mysql`, `performance_schema`, `sys`) dan menyenaraikan semua jadual merentasi pangkalan data lain yang wujud pada pelayan:
     - **`sakila`** (16 jadual pangkalan data contoh MySQL)
     - **`themepark`** (7 jadual pangkalan data taman tema)
     - **`world`** (3 jadual pangkalan data geografi dunia)
     - **`learnnik`** (3 jadual pangkalan data universiti/kursus)
     - **`servlet`** (2 jadual ujian servlet/Java)
     - **Jumlah:** Tepat **31 jadual**.
3. **Implikasi Kejuruteraan:**
   - Pangkalan data `pesgo` adalah bersih sepenuhnya. Tiada sebarang jadual warisan (legacy) atau jadual asing yang patut diguna semula. Kita mempunyai asas yang murni untuk membina skema perusahaan (enterprise-grade schema) yang teguh, mematuhi standard Laravel 12, berskala (scalable), dan bebas daripada hutang teknikal (technical debt).

---

## BAHAGIAN A: EXISTING DATABASE (PANGKALAN DATA SEDIA ADA)

### 1. Senarai 31 Jadual yang Dikesan oleh `db:show` Mengikut Skema

Pemeriksaan metadata melalui `INFORMATION_SCHEMA.TABLES` menunjukkan pemisahan skema seperti berikut:

| No | Nama Jadual | Skema Asal | Jenis | Anggaran Tujuan / Domain Asal |
|---|---|---|---|---|
| 1 | `lecturer` | `learnnik` | BASE TABLE | Menyimpan rekod pensyarah institusi pendidikan |
| 2 | `student` | `learnnik` | BASE TABLE | Menyimpan rekod pelajar institusi pendidikan |
| 3 | `teach` | `learnnik` | BASE TABLE | Jadual hubungan pensyarah dan kursus yang diajar |
| 4 | `actor` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Senarai pelakon filem |
| 5 | `address` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Alamat pelanggan/kedai DVD |
| 6 | `category` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Kategori genre filem |
| 7 | `city` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Bandar geografi |
| 8 | `country` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Negara geografi |
| 9 | `customer` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Pelanggan sewa DVD |
| 10 | `film` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Katalog filem |
| 11 | `film_actor` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Pemetaan pelakon ke filem |
| 12 | `film_category` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Pemetaan kategori ke filem |
| 13 | `film_text` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Indeks teks carian filem |
| 14 | `inventory` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Stok salinan DVD kedai |
| 15 | `language` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Bahasa audio filem |
| 16 | `payment` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Rekod pembayaran sewaan DVD |
| 17 | `rental` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Transaksi sewaan DVD |
| 18 | `staff` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Pekerja kedai sewaan DVD |
| 19 | `store` | `sakila` | BASE TABLE | Pangkalan data sampel MySQL: Cawangan kedai DVD |
| 20 | `userdetails` | `servlet` | BASE TABLE | Skema latihan Java Servlet: Data asas pengguna ringkas |
| 21 | `userdetails2` | `servlet` | BASE TABLE | Skema latihan Java Servlet: Data asas pengguna varian 2 |
| 22 | `attraction` | `themepark` | BASE TABLE | Skema taman tema: Tarikan / permainan taman tema |
| 23 | `employee` | `themepark` | BASE TABLE | Skema taman tema: Kakitangan taman tema |
| 24 | `hours` | `themepark` | BASE TABLE | Skema taman tema: Waktu operasi tarikan taman tema |
| 25 | `sales` | `themepark` | BASE TABLE | Skema taman tema: Resit jualan tiket/produk |
| 26 | `sales_line` | `themepark` | BASE TABLE | Skema taman tema: Item terperinci dalam resit jualan |
| 27 | `themepark` | `themepark` | BASE TABLE | Skema taman tema: Profil cawangan taman tema |
| 28 | `ticket` | `themepark` | BASE TABLE | Skema taman tema: Jenis dan harga tiket masuk |
| 29 | `city` | `world` | BASE TABLE | Pangkalan data sampel MySQL: Bandar dunia |
| 30 | `country` | `world` | BASE TABLE | Pangkalan data sampel MySQL: Maklumat demografi negara |
| 31 | `countrylanguage` | `world` | BASE TABLE | Pangkalan data sampel MySQL: Taburan bahasa negara |

---

### 2. Analisis Terperinci Struktur Jadual Asing yang Menyerupai Pengguna

Walaupun jadual di atas terletak di luar skema `pesgo`, beberapa jadual mempunyai nama yang berpotensi disalah tafsir sebagai sebahagian daripada Modul Pengurusan Pengguna. Berikut adalah struktur teknikalnya:

#### A. Jadual `servlet.userdetails`
- **Tujuan Asal:** Struktur legasi / tutorial Java Servlet untuk pendaftaran laman web mudah.
- **Lajur & Jenis Data:**
  - `username` (VARCHAR, PK)
  - `password` (VARCHAR)
  - `email` (VARCHAR)
  - `phone` (VARCHAR)
- **Kekangan & Hubungan:**
  - Tiada Foreign Key.
  - Kata laluan disimpan tanpa standard keselamatan moden (tiada bcrypt/argon2 hashing standard, tiada audit trail).
  - Tiada medan `created_at` / `updated_at`.
- **Kesesuaian untuk PESGO:** **TIDAK SESUAI**. Merupakan jadual ujian berasingan.

#### B. Jadual `sakila.customer`
- **Tujuan Asal:** Pengurusan pelanggan sewaan DVD dalam skema contoh Sakila.
- **Lajur Utama:** `customer_id` (SMALLINT UNSIGNED, PK), `store_id` (TINYINT), `first_name`, `last_name`, `email`, `address_id` (FK ke `sakila.address`), `active` (BOOLEAN), `create_date`, `last_update`.
- **Kesesuaian untuk PESGO:** **TIDAK SESUAI**. Terikat secara langsung dengan domain kedai sewaan fizikal (`store_id`, `address_id` monolitik).

#### C. Jadual `sakila.staff`
- **Tujuan Asal:** Pengurusan pekerja kaunter kedai DVD.
- **Lajur Utama:** `staff_id`, `first_name`, `last_name`, `address_id`, `picture`, `email`, `store_id`, `active`, `username`, `password`, `last_update`.
- **Kesesuaian untuk PESGO:** **TIDAK SESUAI**. Bukan untuk sistem Personal Shopper / Group Order moden.

#### D. Jadual `sakila.address`
- **Tujuan Asal:** Alamat fizikal berpusat untuk kedai, pekerja, dan pelanggan DVD Sakila.
- **Lajur Utama:** `address_id`, `address`, `address2`, `district`, `city_id` (FK ke `sakila.city`), `postal_code`, `phone`, `location` (GEOMETRY), `last_update`.
- **Kesesuaian untuk PESGO:** **TIDAK SESUAI**. Sistem e-dagang moden memerlukan hubungan satu-ke-banyak (1:N) bagi alamat penghantaran/pembayaran pengguna, penanda alamat utama (`is_primary`), dan label alamat (`Rumah`, `Pejabat`).

---

## BAHAGIAN B: PEMETAAN KEPERLUAN MODUL 1 KEPADA PANGKALAN DATA SEDIA ADA

### 1. Skop Modul 1: Modul Pengurusan Pengguna

Modul 1 PESGO (Personal Shopper and Group Order) merangkumi:
1. **Pendaftaran (Registration):** Pengguna boleh mendaftar sebagai pembeli biasa (Customer/Member), Personal Shopper (Runner/Shopper), atau Pentadbir (Admin).
2. **Log Masuk (Authentication):** Log masuk selamat berasaskan emel/nama pengguna dan kata laluan yang disulitkan menggunakan Bcrypt/Argon2id, pengurusan sesi atau token API (Laravel Sanctum).
3. **Profil Pengguna (User Profile):** Butiran peribadi, nama penuh, nombor telefon, gambar avatar, status pengesahan identiti (terutamanya bagi Personal Shopper).
4. **Alamat Pengguna (Address Book):** Pengguna boleh menyimpan pelbagai alamat (Rumah, Pejabat, dsb.), memilih alamat lalai (default/primary shipping address), poskod, negeri, dan koordinat GPS pilihan untuk penyerahan barang.
5. **Peranan Pengguna (Roles & Permissions):** Pengasingan kuasa yang jelas antara:
   - `Admin` (Pengurusan sistem, semakan transaksi, pengurusan pengguna)
   - `Shopper` / `Personal Shopper` (Penerima pesanan, pengurus pesanan kumpulan, pengemas kini status belian)
   - `Customer` / `Buyer` (Pembuat pesanan individu atau penyertai Group Order)
6. **Pengurusan Akaun (Account Management):** Kemas kini kata laluan, penetapan semula kata laluan (password reset), pengaktifan/penyahaktifan akaun, dan log keselamatan.

### 2. Status Pemetaan Terhadap Skema Sedia Ada

| Keperluan Modul 1 | Wujud dalam Pangkalan Data `pesgo`? | Boleh Guna Semula Jadual Luar? | Keputusan Kejuruteraan |
|---|---|---|---|
| **Users / Authentication** | **TIADA (0 jadual)** | `servlet.userdetails` / `sakila.customer`? **TIDAK** | Perlu bina jadual `users` berasaskan standard Laravel 12. |
| **User Profiles** | **TIADA (0 jadual)** | `servlet.userdetails2`? **TIDAK** | Perlu bina jadual `user_profiles` khusus untuk PESGO. |
| **Addresses** | **TIADA (0 jadual)** | `sakila.address`? **TIDAK** | Perlu bina jadual `user_addresses` (1:N relationship). |
| **Roles & Permissions** | **TIADA (0 jadual)** | Tiada jadual role dalam sebarang skema | Perlu bina jadual `roles` dan `role_user` (RBAC). |
| **Password Reset** | **TIADA (0 jadual)** | Tiada | Menggunakan jadual standard `password_reset_tokens`. |
| **Session / API Tokens** | **TIADA (0 jadual)** | Tiada | Menggunakan standard Laravel `sessions` / `personal_access_tokens`. |

---

## BAHAGIAN C: JURANG (GAPS) & PERUBAHAN DIPERLUKAN

### 1. Analisis Jurang (Gap Analysis)
- Pangkalan data `pesgo` adalah kosong (tabula rasa).
- Tiada sebarang struktur pangkalan data wujud untuk menyokong ciri-ciri Modul 1.
- Percubaan mengimport atau merujuk jadual daripada `sakila`, `world`, `themepark`, atau `servlet` akan mencemari reka bentuk perisian dengan kebergantungan luar konteks (domain model mismatch) dan melanggar prinsip Single Source of Truth bagi perisian perusahaan.

### 2. Struktur Asas Laravel Sedia Ada (Default Scaffolding)
Semasa permulaan projek Laravel 12 di `PESGO/backend`, fail migrasi lalai telah dijana dalam `database/migrations/`:
1. `0001_01_01_000000_create_users_table.php` (Mengandungi `users`, `password_reset_tokens`, `sessions`)
2. `0001_01_01_000001_create_cache_table.php` (Mengandungi `cache`, `cache_locks`)
3. `0001_01_01_000002_create_jobs_table.php` (Mengandungi `jobs`, `job_batches`, `failed_jobs`)

> [!NOTE]
> Migrasi lalai ini **belum dijalankan** pada pangkalan data MySQL `pesgo` bagi mematuhi arahan read-only.
> Apabila fasa pelaksanaan bermula, kita boleh memanfaatkan fail migrasi lalai ini dan memperkayakannya (extend) dengan keperluan spesifik PESGO.

### 3. Potensi Konflik Struktur Lalai Laravel vs Keperluan PESGO
1. **Model `User` Lalai:**
   - Laravel lalai hanya mempunyai medan: `name`, `email`, `email_verified_at`, `password`, `remember_token`.
   - **Keperluan PESGO:** Perlu nombor telefon (`phone_number`), status akaun (`status: active/suspended/pending_verification`), dan peranan (`role`).
   - **Penyelesaian:** Tambah migrasi tersuai atau luaskan migrasi `users` sedia ada untuk memasukkan medan teras ini secara modular.
2. **Penyimpanan Alamat:**
   - Laravel tidak menyediakan jadual alamat secara lalai.
   - **Penyelesaian:** Bina migrasi khusus `create_user_addresses_table`.
3. **Pengurusan Peranan (RBAC):**
   - Laravel lalai tidak menyediakan jadual `roles`.
   - **Penyelesaian:** Bina jadual `roles` dan jadual perantara `model_has_roles` / `role_user` (atau role berasaskan Enum/Jadual RBAC yang kemas dan pantas).

---

## BAHAGIAN D: CADANGAN SENI BINA LARAVEL (RECOMMENDED LARAVEL ARCHITECTURE)

### 1. Cadangan Model Domain (Domain Model Specification)

Bagi memastikan kod kekal bersih, modular, dan mematuhi prinsip Clean Architecture & DDD (Domain-Driven Design), struktur entiti dibahagikan kepada:

1. **`User` (Entiti Teras Pengesahan):**
   - Mengendalikan kredensial log masuk, emel, pengesahan, dan status akaun.
2. **`UserProfile` (Entiti Profil Pengguna):**
   - Menyimpan maklumat peribadi tambahan seperti nama penuh, nombor kad pengenalan / pasport (jika diperlukan untuk pengesahan Personal Shopper), avatar URL, bio, dan maklumat bank (untuk bayaran balik/pembayaran shopper).
3. **`UserAddress` (Entiti Alamat):**
   - Menyimpan pelbagai alamat penghantaran dan pengebilan untuk seorang pengguna.
4. **`Role` & `Permission` (Entiti Kawalan Akses):**
   - Mengendalikan Role-Based Access Control (RBAC).

---

### 2. Rajah Hubungan Entiti (ERD) Modul 1 (ASCII / Text Format)

```text
========================================================================================
                                 PESGO MODULE 1 ERD
========================================================================================

   +--------------------------+
   |          roles           |
   +--------------------------+
   | PK id          BIGINT    |
   |    name        VARCHAR   |<----------+
   |    slug        VARCHAR   |           |
   |    description VARCHAR   |           |
   |    created_at  TIMESTAMP |           |
   |    updated_at  TIMESTAMP |           |
   +--------------------------+           |
                |                         |
                | 1                       |
                |                         |
                | N                       |
   +--------------------------+           |
   |        role_user         |           |
   +--------------------------+           |
   | PK/FK role_id  BIGINT    |-----------+
   | PK/FK user_id  BIGINT    |-----------+
   |       created_at TIMESTAMP|          |
   +--------------------------+          |
                                         |
                                         | 1
   +--------------------------+          |
   |          users           |          |
   +--------------------------+          |
   | PK id                BIGINT|<-------+
   |    email             VARCHAR (Unique)
   |    phone_number      VARCHAR (Unique, Nullable)
   |    password          VARCHAR (Hashed)
   |    status            ENUM ('active', 'inactive', 'suspended')
   |    email_verified_at TIMESTAMP (Nullable)
   |    phone_verified_at TIMESTAMP (Nullable)
   |    remember_token    VARCHAR (Nullable)
   |    created_at        TIMESTAMP
   |    updated_at        TIMESTAMP
   |    deleted_at        TIMESTAMP (SoftDeletes)
   +--------------------------+
          |            |
       1  |            | 1
          |            |
       1  |            | N
          v            v
   +--------------------------+     +-------------------------------+
   |      user_profiles       |     |        user_addresses         |
   +--------------------------+     +-------------------------------+
   | PK id            BIGINT  |     | PK id              BIGINT     |
   | FK user_id       BIGINT  |     | FK user_id         BIGINT     |
   |    first_name    VARCHAR |     |    label           VARCHAR    | -- 'Rumah', 'Ofis'
   |    last_name     VARCHAR |     |    recipient_name  VARCHAR    |
   |    ic_number     VARCHAR |     |    recipient_phone VARCHAR    |
   |    avatar_url    VARCHAR |     |    address_line_1  VARCHAR    |
   |    shopper_status ENUM   |     |    address_line_2  VARCHAR    |
   |    rating_avg    DECIMAL |     |    postcode        VARCHAR    |
   |    created_at    TIMESTAMP|    |    city            VARCHAR    |
   |    updated_at    TIMESTAMP|    |    state           VARCHAR    |
   +--------------------------+     |    is_primary      BOOLEAN    |
                                    |    latitude        DECIMAL    | (Pilihan GPS)
                                    |    longitude       DECIMAL    | (Pilihan GPS)
                                    |    created_at      TIMESTAMP  |
                                    |    updated_at      TIMESTAMP  |
                                    +-------------------------------+

   +------------------------------------+
   |       password_reset_tokens        |
   +------------------------------------+
   | PK email      VARCHAR              |
   |    token      VARCHAR              |
   |    created_at TIMESTAMP (Nullable) |
   +------------------------------------+

   +------------------------------------+
   |              sessions              |
   +------------------------------------+
   | PK id            VARCHAR           |
   | FK user_id       BIGINT (Nullable) |
   |    ip_address    VARCHAR (Nullable)|
   |    user_agent    TEXT (Nullable)   |
   |    payload       LONGTEXT          |
   |    last_activity INT               |
   +------------------------------------+
```

---

### 3. Struktur Komponen Laravel yang Dicadangkan

#### A. Models & Relationships (Ruang Nama: `App\Models`)
1. **`User`** (`app/Models/User.php`)
   - `hasOne(UserProfile::class)`
   - `hasMany(UserAddress::class)`
   - `belongsToMany(Role::class, 'role_user')`
   - Kaedah pembantu peranan: `hasRole($roleSlug)`, `isShopper()`, `isAdmin()`.
2. **`UserProfile`** (`app/Models/UserProfile.php`)
   - `belongsTo(User::class)`
3. **`UserAddress`** (`app/Models/UserAddress.php`)
   - `belongsTo(User::class)`
   - Scope: `scopePrimary()`.
4. **`Role`** (`app/Models/Role.php`)
   - `belongsToMany(User::class, 'role_user')`

#### B. Controllers (Ruang Nama: `App\Http\Controllers\Api\V1\Auth` & `User`)
Pemisahan Controller mengikut prinsip Single Responsibility (SRP):
- `AuthController` (Daftar, Log Masuk, Log Keluar, Refresh Token)
- `PasswordResetController` (Lupa Kata Laluan, Tukar Kata Laluan)
- `ProfileController` (Lihat & Kemas kini Profil)
- `AddressController` (CRUD Alamat, Set Alamat Utama)
- `AccountController` (Tukar Kata Laluan, Nyahaktifkan Akaun)
- `AdminUserController` (Pengurusan Pengguna oleh Pentadbir)

#### C. Form Requests (Ruang Nama: `App\Http\Requests\Auth` & `User`)
Pengesahan data (validation) diasingkan sepenuhnya daripada Controller:
- `RegisterRequest` (Pengesahan nama, emel unik, kata laluan kuat, nombor telefon, jenis peranan)
- `LoginRequest` (Pengesahan kredensial log masuk dan 'rate limiting')
- `UpdateProfileRequest`
- `StoreAddressRequest`
- `UpdateAddressRequest`
- `UpdatePasswordRequest`

#### D. Services / Actions (Ruang Nama: `App\Services` atau `App\Actions`)
Bagi memastikan kod boleh diuji (testable) dan mengelakkan "Fat Controllers":
- `RegisterUserService`: Menguruskan penciptaan rekod transaksi (`users`, `user_profiles`, penyerahan `roles`, dan penghantaran emel verifikasi dalam DB Transaction).
- `AuthenticationService`: Menguruskan pengesahan kata laluan, penjanaan token Sanctum, dan log keselamatan.
- `AddressManagementService`: Mengendalikan pertukaran alamat utama (memastikan hanya satu alamat `is_primary = true` bagi setiap pengguna).

#### E. Strategi Pengesahan (Authentication Strategy)
- Menggunakan **Laravel Sanctum** (token bearer API yang selamat, standard industri untuk SPA dan Mobile App).
- Membolehkan frontend masa depan berhubung secara RESTful tanpa masalah CORS/CSRF yang rumit.

#### F. Laluan API yang Dicadangkan (Routes: `routes/api.php`)
```php
// Laluan Awam (Public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// Laluan Terlindung (Protected by auth:sanctum)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Auth & Akaun
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/account/password', [AccountController::class, 'updatePassword']);
    Route::delete('/account', [AccountController::class, 'deactivateAccount']);

    // Profil
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Alamat
    Route::apiResource('/addresses', AddressController::class);
    Route::patch('/addresses/{address}/set-primary', [AddressController::class, 'setPrimary']);

    // Pentadbiran Pengguna (Admin Sahaja)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
        Route::patch('/users/{user}/roles', [AdminUserController::class, 'updateRoles']);
    });
});
```

---

## Ringkasan Pelan Tindakan Seterusnya (Next Steps)

Dokumen analisis ini membuktikan bahawa pangkalan data `pesgo` adalah bersih dan bersedia untuk direkabentuk secara profesional.

Langkah seterusnya yang dicadangkan selepas semakan dan kelulusan anda:
1. **Penyediaan Migrasi Pangkalan Data Modul 1:**
   - Menyesuaikan migrasi lalai `users`.
   - Mencipta migrasi untuk `roles` dan `role_user`.
   - Mencipta migrasi untuk `user_profiles`.
   - Mencipta migrasi untuk `user_addresses`.
2. **Menjalankan Migrasi ke MySQL `pesgo`:**
   - Melaksanakan `php artisan migrate` bagi menjana skema rasmi yang lengkap.
3. **Pembangunan Kod Sumber Modul 1:**
   - Model & Hubungan Eloquent.
   - Pakej Pengesahan API (Laravel Sanctum).
   - Form Requests, Services, Controllers, dan Routes.
