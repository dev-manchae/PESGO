# Modul 1 — Fasa 1: Eloquent Models & Relationships

**Projek:** PESGO — Personal Shopper and Group Order  
**Modul:** Modul 1 — Modul Pengurusan Pengguna (User Management Module)  
**Fasa:** Fasa 1 — Eloquent Models & Relationships  
**Status:** COMPLETED (SELESAI)  
**Rangka Kerja:** Laravel 12.68.0 | PHP 8.2.12 | MySQL 8.0.31 (`pesgo`)  
**Dokumen Rujukan Rasmi:** `Phase 1 - Eloquent Models & Relationships.md` (English)  

---

## 1. Tujuan

Tujuan utama pelaksanaan **Fasa 1** adalah untuk membina asas **Eloquent ORM Model** bagi Modul 1 (Modul Pengurusan Pengguna) dalam aplikasi backend PESGO.

Fasa ini memetakan lapisan domain (*domain layer*) aplikasi Laravel secara terus kepada skema pangkalan data MySQL 8.0 `pesgo` yang telah diluluskan dan dimigrasikan secara rasmi. Melalui fasa ini, kelas-kelas PHP dicipta untuk mewakili identiti pengguna, profil peribadi, buku alamat, dan kawalan akses peranan (*Role-Based Access Control / RBAC*), lengkap dengan penetapan atribut boleh diisi (*fillable*), atribut tersembunyi (*hidden*), penukaran jenis (*casts*), dan pemetaan hubungan (*relationships*) dua hala yang tepat.

---

## 2. Skop Fasa 1

### Perkara yang Termasuk dalam Fasa 1
Fasa 1 memberi tumpuan khusus kepada asas model Eloquent dan hubungannya:
- **Model `User`:** Entiti akar pengesahan akaun pengguna (`app/Models/User.php`).
- **Model `UserProfile`:** Entiti profil peribadi dan status pengesahan Personal Shopper (`app/Models/UserProfile.php`).
- **Model `UserAddress`:** Entiti buku alamat penghantaran dan pengebilan (`app/Models/UserAddress.php`).
- **Model `Role`:** Entiti katalog peranan sistem (`app/Models/Role.php`).
- **Eloquent Relationships:** Hubungan `hasOne`, `hasMany`, `belongsTo`, dan `belongsToMany` antara entiti.
- **Sokongan SoftDeletes:** Mengaktifkan trait `SoftDeletes` pada model yang mempunyai lajur `deleted_at` dalam skema pangkalan data.

### Perkara yang BELUM Dibina (Di Luar Skop Fasa 1)
Komponen berikut **tidak dibina** dalam fasa ini dan ditangguhkan ke fasa-fasa berikutnya secara berperingkat:
- Aliran pendaftaran (*registration*) dan log masuk (*login*).
- Pengesahan API menggunakan token Laravel Sanctum atau pengurusan sesi.
- PHP Backed Enums (`UserStatus`, `ShopperStatus`, `RoleSlug`) dan Enum model casts (dikhaskan untuk Fasa 2).
- Penyemaian peranan pangkalan data (*RoleSeeder*) dan penyerahan peranan automatik (dikhaskan untuk Fasa 3).
- Lapisan perkhidmatan (*Services*), *Actions*, dan logik perniagaan domain.
- Pengawal HTTP (*Controllers*), *Form Requests*, validasi input, dan laluan API (*Routes*).
- *Authorization*, *Policies*, *Gates*, dan *Middleware*.
- Aliran permohonan Personal Shopper dan kelulusan Admin.
- Logik penghapusan bertingkat (*cascading soft delete*) dan pengaktifan semula akaun.
- Modul-modul perniagaan PESGO seterusnya (Modul 2: Pesanan Kumpulan, Katalog Produk, Pembayaran, Penghantaran).

---

## 3. Jadual Database Yang Berkaitan

Fasa 1 memetakan lima (5) jadual fizikal sedia ada di dalam database `pesgo`:

| Nama Jadual | Model Eloquent | Jenis Hubungan | Peranan & Fungsi dalam PESGO |
|---|---|---|---|
| `users` | `User` | Entiti Induk (*Root*) | Menyimpan kredensial log masuk teras (emel, kata laluan hash, nama paparan, status akaun, cap masa sistem, dan pemadaman lembut). |
| `user_profiles` | `UserProfile` | 1:1 dengan `users` | Menyimpan identiti rasmi pengguna (nama penuh rasmi, nombor telefon, nombor pengenalan/IC, bio, avatar, dan status onboarding Personal Shopper). |
| `user_addresses` | `UserAddress` | 1:N dengan `users` | Menyimpan pelbagai alamat penghantaran dan pengebilan pengguna, termasuk poskod, koordinat geografi (lat/long), dan penanda alamat utama. |
| `roles` | `Role` | Entiti Katalog | Menyimpan senarai peranan sistem (`customer`, `shopper`, `admin`). |
| `role_user` | Pivot Table | M:N (`users` ↔ `roles`) | Jadual perantara yang menghubungkan pengguna kepada pelbagai peranan serentak (menyokong dwi-persona: Pelanggan + Shopper). |

---

## 4. Model Yang Dibina

### 4.1. User (`app/Models/User.php`)

Model `User` merupakan entiti keselamatan dan identiti teras bagi keseluruhan ekosistem PESGO.

- **Lokasi Fail:** `app/Models/User.php`
- **Jadual Database:** `users`
- **Pewarisan Kelas:** `Illuminate\Foundation\Auth\User as Authenticatable`
- **Traits yang Digunakan:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Notifications\Notifiable`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Atribut Boleh Diisi (`$fillable`):**
  - `name`: Nama paparan pengguna / username.
  - `email`: Emel log masuk utama (unik di peringkat pangkalan data).
  - `password`: Kata laluan hash (Bcrypt).
  - `status`: Status akaun (`active`, `pending`, `suspended`, `deactivated`).
- **Atribut Tersembunyi (`$hidden`):**
  - `password`: Disembunyikan daripada output JSON/array.
  - `remember_token`: Disembunyikan daripada output JSON/array.
- **Penukaran Jenis (`casts()`):**
  - `email_verified_at` → `datetime`
  - `password` → `hashed`
- **Relationship yang Dibina:**
  - `profile()`: Hubungan HasOne kepada `UserProfile`.
  - `addresses()`: Hubungan HasMany kepada `UserAddress`.
  - `roles()`: Hubungan BelongsToMany kepada `Role` melalui jadual pivot `role_user`.

### 4.2. UserProfile (`app/Models/UserProfile.php`)

Model `UserProfile` menyimpan butiran rasmi peribadi serta menjejak kitaran hayat permohonan Personal Shopper.

- **Lokasi Fail:** `app/Models/UserProfile.php`
- **Jadual Database:** `user_profiles`
- **Pewarisan Kelas:** `Illuminate\Database\Eloquent\Model`
- **Traits yang Digunakan:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Atribut Boleh Diisi (`$fillable`):**
  - `user_id`: Kunci asing merujuk kepada `users.id`.
  - `full_name`: Nama penuh rasmi mengikut dokumen pengenalan diri.
  - `phone_number`: Nombor telefon perhubungan (unik, nullable).
  - `phone_verified_at`: Cap masa pengesahan nombor telefon.
  - `identification_no`: Nombor kad pengenalan / pasport (unik, nullable untuk pembeli biasa, wajib untuk shopper).
  - `avatar_url`: Pautan URL gambar profil.
  - `bio`: Penerangan ringkas profil / keterangan perkhidmatan shopper.
  - `shopper_status`: Status permohonan shopper (`none`, `pending`, `approved`, `rejected`).
  - `shopper_verified_at`: Cap masa apabila permohonan shopper diluluskan oleh admin.
- **Atribut Tersembunyi (`$hidden`):**
  - `identification_no`: Disembunyikan daripada serialization JSON lalai bagi melindungi maklumat sensitif peribadi (PII).
- **Penukaran Jenis (`casts()`):**
  - `phone_verified_at` → `datetime`
  - `shopper_verified_at` → `datetime`
- **Relationship yang Dibina:**
  - `user()`: Hubungan songsang BelongsTo kepada `User`.

### 4.3. UserAddress (`app/Models/UserAddress.php`)

Model `UserAddress` menguruskan rekod alamat penghantaran dan pengebilan bagi setiap pengguna.

- **Lokasi Fail:** `app/Models/UserAddress.php`
- **Jadual Database:** `user_addresses`
- **Pewarisan Kelas:** `Illuminate\Database\Eloquent\Model`
- **Traits yang Digunakan:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Atribut Boleh Diisi (`$fillable`):**
  - `user_id`: Kunci asing merujuk kepada `users.id`.
  - `label`: Label alamat (contoh: 'Rumah', 'Pejabat').
  - `recipient_name`: Nama penerima bungkusan.
  - `recipient_phone`: Nombor telefon penerima.
  - `address_line_1`: Baris alamat utama.
  - `address_line_2`: Baris alamat tambahan (unit, blok, bangunan).
  - `postcode`: Poskod kawasan.
  - `city`: Bandar.
  - `state`: Negeri.
  - `country_code`: Kod negara 2 huruf (lalai: `'MY'`).
  - `latitude`: Koordinat latitud geografi.
  - `longitude`: Koordinat longitud geografi.
  - `is_default_shipping`: Penanda boolean alamat penghantaran utama.
  - `is_default_billing`: Penanda boolean alamat pengebilan utama.
  - `delivery_instructions`: Arahan khas kepada kurier penghantaran.
- **Penukaran Jenis (`casts()`):**
  - `latitude` → `decimal:8`
  - `longitude` → `decimal:8`
  - `is_default_shipping` → `boolean`
  - `is_default_billing` → `boolean`
- **Relationship yang Dibina:**
  - `user()`: Hubungan songsang BelongsTo kepada `User`.

### 4.4. Role (`app/Models/Role.php`)

Model `Role` mentakrifkan peranan dan kebenaran asas pengguna dalam sistem.

- **Lokasi Fail:** `app/Models/Role.php`
- **Jadual Database:** `roles`
- **Pewarisan Kelas:** `Illuminate\Database\Eloquent\Model`
- **Traits yang Digunakan:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
- **Penggunaan SoftDeletes:**
  - **TIDAK DIGUNAKAN.** Jadual fizikal `roles` dalam pangkalan data tidak mempunyai lajur `deleted_at`. Peranan sistem merupakan takrifan kekal yang tidak dipadam secara lembut.
- **Atribut Boleh Diisi (`$fillable`):**
  - `name`: Nama peranan paparan (contoh: 'Customer', 'Personal Shopper', 'Administrator').
  - `slug`: Pengenal pasti sistem (contoh: `'customer'`, `'shopper'`, `'admin'`).
  - `description`: Penerangan skop peranan.
- **Relationship yang Dibina:**
  - `users()`: Hubungan Many-to-Many (BelongsToMany) kepada `User` melalui jadual pivot `role_user`.

---

## 5. Struktur Relationship

### Rajah Hubungan Model (Relationship Diagram)

```text
                  +-----------------------+
                  |         User          |
                  +-----------------------+
                    |         |         |
           hasOne   |         | hasMany | belongsToMany
           (1:1)    |         | (1:N)   | (M:N via role_user)
                    v         v         v
             +------------+ +------------+ +------------+
             |UserProfile | |UserAddress | |    Role    |
             +------------+ +------------+ +------------+
                    |         |                 |
          belongsTo |         | belongsTo       | belongsToMany
                    +---------+-----------------+
```

### Penjelasan Terperinci Setiap Relationship

1. **User → UserProfile (`HasOne`):**
   * Kaedah: `$user->profile()`
   * Foreign Key: `user_profiles.user_id`
   * Local Key: `users.id`
   * Hubungan 1 kepada 1: Setiap akaun pengguna mempunyai tepat satu rekod profil peribadi.

2. **User → UserAddress (`HasMany`):**
   * Kaedah: `$user->addresses()`
   * Foreign Key: `user_addresses.user_id`
   * Local Key: `users.id`
   * Hubungan 1 kepada Banyak: Seorang pengguna boleh menyimpan sifar, satu, atau banyak alamat penghantaran.

3. **User → Role (`BelongsToMany`):**
   * Kaedah: `$user->roles()`
   * Pivot Table: `role_user`
   * Foreign Pivot Key: `user_id`
   * Related Pivot Key: `role_id`
   * Konfigurasi Pivot: Menggunakan `withPivot('created_at')`.  
     *(Nota: Jadual fizikal `role_user` hanya mempunyai lajur `created_at` tanpa `updated_at`. Oleh itu, `withTimestamps()` tidak digunakan bagi mengelakkan ralat SQL berkaitan ketiadaan lajur `updated_at`).*

4. **UserProfile → User (`BelongsTo`):**
   * Kaedah: `$profile->user()`
   * Foreign Key: `user_profiles.user_id`
   * Owner Key: `users.id`
   * Hubungan songsang yang membolehkan profil merujuk kembali kepada akaun pengguna induk.

5. **UserAddress → User (`BelongsTo`):**
   * Kaedah: `$address->user()`
   * Foreign Key: `user_addresses.user_id`
   * Owner Key: `users.id`
   * Hubungan songsang yang membolehkan alamat merujuk kepada pemilik akaunnya.

6. **Role → User (`BelongsToMany`):**
   * Kaedah: `$role->users()`
   * Pivot Table: `role_user`
   * Foreign Pivot Key: `role_id`
   * Related Pivot Key: `user_id`
   * Konfigurasi Pivot: Menggunakan `withPivot('created_at')`.

---

## 6. SoftDeletes

### Konsep SoftDeletes dalam PESGO
SoftDeletes (Pemadaman Lembut) membolehkan rekod "dipadam" daripada pandangan aplikasi biasa tanpa membuang baris data secara fizikal daripada cakera pangkalan data. Apabila model dipadam menggunakan `$model->delete()`, Eloquent mengemas kini lajur `deleted_at` kepada tarikh dan masa semasa (`NOW()`). Semua pertanyaan Eloquent seterusnya secara automatik menyaring rekod dengan syarat `WHERE deleted_at IS NULL`.

### Penggunaan SoftDeletes Mengikut Model
* **`User`:** Menggunakan `SoftDeletes` (`users.deleted_at`).
* **`UserProfile`:** Menggunakan `SoftDeletes` (`user_profiles.deleted_at`).
* **`UserAddress`:** Menggunakan `SoftDeletes` (`user_addresses.deleted_at`).
* **`Role`:** **TIDAK** menggunakan `SoftDeletes` (tiada lajur `deleted_at` dalam jadual `roles`).

> [!IMPORTANT]
> **PENGESAHAN PENTING MENGENAI CASCADING SOFT DELETE:**  
> Dalam MySQL InnoDB, peraturan kekangan kunci asing fizikal `ON DELETE CASCADE` **hanya beroperasi semasa Hard Delete (pemadaman fizikal sebenar)**. MySQL tidak memicu penghapusan anak sekiranya rekod induk sekadar dikemas kini dengan cap masa pemadaman lembut.
> 
> Dalam Fasa 1, logik penghapusan bertingkat automatik (*cascading soft delete/restore hooks*) **BELUM DILAKSANAKAN**. Logik ini dikhaskan untuk dibangunkan dalam lapisan Domain Services pada fasa pengurusan akaun kelak.

---

## 7. Asas Peranan (Role Foundation)

Model Eloquent yang dibina dalam Fasa 1 menyokong sepenuhnya keperluan model perniagaan PESGO:

- **Peranan yang Disokong:**
  - `customer`: Pelanggan biasa (pembeli).
  - `shopper`: Personal Shopper yang telah disahkan untuk menerima pesanan belian.
  - `admin`: Pentadbir platform PESGO.
- **Sokongan Dwi-Persona (Non-Exclusive Roles):**
  Seorang individu yang sama (`users.id = 1`) boleh memegang peranan `customer` dan peranan `shopper` secara **serentak** di dalam jadual `role_user`. Pengguna tidak perlu mendaftar akaun kedua untuk bertindak sebagai Personal Shopper.
- **Peraturan Pesanan Kumpulan (Group Order Rule):**
  **Group Order BUKAN peranan sistem.** Penyertaan dalam Group Order merupakan aktiviti perniagaan/transaksi di bawah **Modul 2**. Tiada sebarang peranan bernama `group_order` dibina dalam Modul 1.

---

## 8. Konfigurasi Model

Jadual rujukan konfigurasi sebenar yang wujud di dalam kod model Fasa 1:

| Model | Nama Jadual | SoftDeletes | Kunci Primer | Atribut Boleh Diisi (`$fillable`) | Atribut Tersembunyi (`$hidden`) | Penukaran Jenis (`casts()`) |
|---|---|---|---|---|---|---|
| **`User`** | `users` | Ya | `id` (Auto-increment) | `name`, `email`, `password`, `status` | `password`, `remember_token` | `email_verified_at` (datetime), `password` (hashed) |
| **`UserProfile`** | `user_profiles` | Ya | `id` (Auto-increment) | `user_id`, `full_name`, `phone_number`, `phone_verified_at`, `identification_no`, `avatar_url`, `bio`, `shopper_status`, `shopper_verified_at` | `identification_no` | `phone_verified_at` (datetime), `shopper_verified_at` (datetime) |
| **`UserAddress`** | `user_addresses` | Ya | `id` (Auto-increment) | `user_id`, `label`, `recipient_name`, `recipient_phone`, `address_line_1`, `address_line_2`, `postcode`, `city`, `state`, `country_code`, `latitude`, `longitude`, `is_default_shipping`, `is_default_billing`, `delivery_instructions` | Tiada | `latitude` (decimal:8), `longitude` (decimal:8), `is_default_shipping` (boolean), `is_default_billing` (boolean) |
| **`Role`** | `roles` | Tidak | `id` (Auto-increment) | `name`, `slug`, `description` | Tiada | Tiada |

---

## 9. Pengesahan yang Dilakukan (Verification)

Sepanjang penyempurnaan Fasa 1, pemeriksaan baca-sahaja (*read-only*) dan ujian selamat berikut telah dilaksanakan:

1. **Ujian Sintaks PHP CLI (`php -l`):**
   * Semua fail model disemak dan lulus tanpa sebarang ralat sintaks (*No syntax errors detected*).
2. **Pemeriksaan Refleksi Hubungan Eloquent (Tinker):**
   * Disahkan bahawa memanggil `$user->profile()` menghasilkan instans `HasOne`.
   * Disahkan bahawa memanggil `$user->addresses()` menghasilkan instans `HasMany`.
   * Disahkan bahawa memanggil `$user->roles()` menghasilkan instans `BelongsToMany`.
   * Disahkan bahawa memanggil `$profile->user()` dan `$address->user()` menghasilkan instans `BelongsTo`.
   * Disahkan bahawa memanggil `$role->users()` menghasilkan instans `BelongsToMany`.
3. **Pemeriksaan Trait SoftDeletes:**
   * Pengesahan melalui `class_uses_recursive()` membuktikan trait `SoftDeletes` aktif pada `User`, `UserProfile`, dan `UserAddress`, serta tiada pada `Role`.
4. **Pemeriksaan Bilangan Baris Pangkalan Data:**
   * Pemeriksaan jadual mengesahkan bilangan rekod kekal **0** merentasi semua jadual (`users`, `user_profiles`, `user_addresses`, `roles`, `role_user`).

---

## 10. Keselamatan Database (Database Safety)

- **Tiada Perubahan Skema:** Tiada jadual, lajur, atau kekangan pangkalan data yang ditambah, diubah suai, atau dipadam.
- **Tiada Pelaksanaan Migrasi:** Arahan `migrate`, `migrate:fresh`, mahupun `migrate:rollback` tidak dijalankan.
- **Integriti Data Terpelihara:** Pangkalan data MySQL `pesgo` kekal utuh dengan 13 jadual bersih tanpa sebarang data palsu dimasukkan.

---

## 11. Status Semasa

```text
Modul 1 — Modul Pengurusan Pengguna
Fasa 1 — Eloquent Models & Relationships
Status: COMPLETED (SELESAI)
```

Fasa 2 **belum dilaksanakan** pada ketika dokumentasi ini ditulis.

---

## 12. Fasa Seterusnya

Fasa berikutnya yang dirancang bagi Modul 1 adalah:

> **Phase 2 — Enums & Eloquent Casts**  
> Mentakrifkan PHP 8.2 Backed Enums (`UserStatus`, `ShopperStatus`, `RoleSlug`) dan memetakan Enums tersebut ke dalam `$casts` pada model yang berkaitan bagi menjamin keselamatan jenis (*type safety*).
