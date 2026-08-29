# Module 1 — Phase 2: Enums & Eloquent Casts

**Project:** PESGO — Personal Shopper and Group Order  
**Module:** Module 1 — Modul Pengurusan Pengguna (User Management Module)  
**Phase:** Phase 2 — Enums & Eloquent Casts  
**Status:** COMPLETED  
**Framework:** Laravel 12.68.0 | PHP 8.2.12 | MySQL 8.0.31 (`pesgo`)  
**Previous Phase:** `Phase 1 - Eloquent Models & Relationships.md`  

---

## 1. Purpose

The purpose of Phase 2 is to establish **strict type safety** for discrete domain states across the Module 1 Eloquent models. 

By defining native **PHP 8.2 String-Backed Enums** and registering them within Laravel 12 model casts, the application eliminates arbitrary string literals ("magic strings") when interacting with user account statuses, personal shopper onboarding statuses, and system role identifiers. When attributes are retrieved from or assigned to Eloquent models, they are automatically cast to and from strongly-typed Enum instances, preventing invalid states at compile time and runtime.

---

## 2. Scope

### In Scope for Phase 2
Phase 2 covers exclusively the creation of PHP Backed Enums and their binding to existing models:
- **`UserStatus` Enum:** Backed Enum representing account lifecycle statuses (`app/Enums/UserStatus.php`).
- **`ShopperStatus` Enum:** Backed Enum representing personal shopper verification statuses (`app/Enums/ShopperStatus.php`).
- **`RoleSlug` Enum:** Backed Enum representing standard system role identifiers (`app/Enums/RoleSlug.php`).
- **Eloquent Model Casts:**
  - `User::$casts`: Casting `status` attribute to `UserStatus`.
  - `UserProfile::$casts`: Casting `shopper_status` attribute to `ShopperStatus`.
  - `Role::$casts`: Casting `slug` attribute to `RoleSlug`.
- **Regression Verification:** Ensuring Phase 1 relationships and SoftDeletes configurations remain 100% intact.

### Out of Scope for Phase 2
The following concerns were intentionally **not implemented** in this phase:
- Adding business logic, validation rules, or service methods inside the Enums.
- Role seeding (`RoleSeeder`) and assigning roles to users (deferred to Phase 3).
- Role helper methods on `User` such as `hasRole()`, `isShopper()`, `isAdmin()` (deferred to Phase 3).
- Authentication, Sanctum tokens, registration, and login flows.
- Profile and address management services.
- Shopper onboarding submission and Admin approval workflows.
- HTTP Controllers, Form Requests, Middleware, and Routes.
- Database migrations, schema changes, or test data insertion.

---

## 3. Database Schema Alignment

The backing values of the Enums match the physical columns and constraints in the MySQL 8.0 `pesgo` database schema with zero deviation:

| Database Column | Table | Column Type in MySQL | Backed Enum Class | Approved Values |
|---|---|---|---|---|
| `users.status` | `users` | `ENUM('active','pending','suspended','deactivated')` | `App\Enums\UserStatus` | `'active'`, `'pending'`, `'suspended'`, `'deactivated'` |
| `user_profiles.shopper_status` | `user_profiles` | `ENUM('none','pending','approved','rejected')` | `App\Enums\ShopperStatus` | `'none'`, `'pending'`, `'approved'`, `'rejected'` |
| `roles.slug` | `roles` | `VARCHAR(50)` | `App\Enums\RoleSlug` | `'customer'`, `'shopper'`, `'admin'` |

---

## 4. Implemented PHP Backed Enums

### 4.1. UserStatus (`app/Enums/UserStatus.php`)

Defines all valid operational states for a registered PESGO user account.

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

- **File Location:** `app/Enums/UserStatus.php`
- **Backing Type:** `string`
- **Cases:**
  - `ACTIVE ('active')`: Normal active account with full system access.
  - `PENDING ('pending')`: Newly registered account awaiting email/phone verification.
  - `SUSPENDED ('suspended')`: Account restricted by administrator due to policy violations.
  - `DEACTIVATED ('deactivated')`: Account voluntarily closed or deactivated by the user.

### 4.2. ShopperStatus (`app/Enums/ShopperStatus.php`)

Defines the lifecycle states of a customer's application to become a Personal Shopper.

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

- **File Location:** `app/Enums/ShopperStatus.php`
- **Backing Type:** `string`
- **Cases:**
  - `NONE ('none')`: Default state; user has not applied to become a Personal Shopper.
  - `PENDING ('pending')`: Application submitted with IC/passport and awaiting Admin review.
  - `APPROVED ('approved')`: Application approved; user officially possesses the Personal Shopper persona.
  - `REJECTED ('rejected')`: Application rejected by Admin (user may reapply with corrected documents).

### 4.3. RoleSlug (`app/Enums/RoleSlug.php`)

Defines the system identifiers for PESGO roles within the RBAC structure.

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

- **File Location:** `app/Enums/RoleSlug.php`
- **Backing Type:** `string`
- **Cases:**
  - `CUSTOMER ('customer')`: Base persona for normal buyers and consumers.
  - `SHOPPER ('shopper')`: Personal Shopper persona authorized to accept purchase requests.
  - `ADMIN ('admin')`: Platform administrator.
- **Architecture Note:** Group Order is **NOT** a system role and is therefore intentionally absent from this Enum.

---

## 5. Model Eloquent Casts

Each model was updated to cast its corresponding column using Laravel 12's `casts()` method convention. All existing casts from Phase 1 were strictly preserved.

### 5.1. User Model (`app/Models/User.php`)

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

### 5.2. UserProfile Model (`app/Models/UserProfile.php`)

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

### 5.3. Role Model (`app/Models/Role.php`)

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

## 6. Type Safety & Developer Experience Benefits

1. **Elimination of Magic Strings:** Developers interact with `UserStatus::ACTIVE` instead of raw string `'active'`, preventing typos (e.g., `'activ'`, `'Active'`).
2. **IDE Autocompletion:** Code editors provide instant autocompletion for all valid enum cases and values.
3. **Automatic Serialization & Deserialization:**
   - Writing: Setting `$user->status = UserStatus::ACTIVE` causes Eloquent to automatically persist the string value `'active'` to the database.
   - Reading: Retrieving `$user->status` returns an instance of `App\Enums\UserStatus`.
4. **Type-Hinting in Domain Services:** Future service methods can strictly type-hint parameters (e.g., `public function updateStatus(User $user, UserStatus $status): void`), catching type mismatches before runtime.

---

## 7. Phase 1 Regression & Compatibility Verification

During Phase 2, all architectural decisions and implementations from Phase 1 were verified to remain completely unaffected:

| Model | Relationships Preserved | SoftDeletes Preserved | Mass-Assignment `$fillable` Preserved |
|---|---|---|---|
| **`User`** | `profile()` (HasOne), `addresses()` (HasMany), `roles()` (BelongsToMany) | Yes (`SoftDeletes`) | Yes (`name`, `email`, `password`, `status`) |
| **`UserProfile`** | `user()` (BelongsTo) | Yes (`SoftDeletes`) | Yes (`user_id`, `full_name`, `phone_number`, `identification_no`, etc.) |
| **`UserAddress`** | `user()` (BelongsTo) | Yes (`SoftDeletes`) | Yes (`user_id`, `recipient_name`, `latitude`, `longitude`, etc.) |
| **`Role`** | `users()` (BelongsToMany) | No (Correct, per database schema) | Yes (`name`, `slug`, `description`) |

---

## 8. Verification Performed

The following safe, non-destructive verification procedures were executed:

1. **PHP CLI Syntax Validation (`php -l`):**
   - `app/Enums/UserStatus.php`: Passed (0 errors).
   - `app/Enums/ShopperStatus.php`: Passed (0 errors).
   - `app/Enums/RoleSlug.php`: Passed (0 errors).
   - `app/Models/User.php`: Passed (0 errors).
   - `app/Models/UserProfile.php`: Passed (0 errors).
   - `app/Models/Role.php`: Passed (0 errors).
   - `app/Models/UserAddress.php`: Passed (0 errors).

2. **Enum Case & Value Inspection (via `php artisan tinker`):**
   - Verified that `UserStatus::cases()` yields exactly 4 cases matching `'active'`, `'pending'`, `'suspended'`, `'deactivated'`.
   - Verified that `ShopperStatus::cases()` yields exactly 4 cases matching `'none'`, `'pending'`, `'approved'`, `'rejected'`.
   - Verified that `RoleSlug::cases()` yields exactly 3 cases matching `'customer'`, `'shopper'`, `'admin'`.

3. **In-Memory Casting Verification (without modifying database records):**
   - Verified `$user->status = 'active'` yields `$user->status instanceof App\Enums\UserStatus` (`true`).
   - Verified `$profile->shopper_status = 'pending'` yields `$profile->shopper_status instanceof App\Enums\ShopperStatus` (`true`).
   - Verified `$role->slug = 'customer'` yields `$role->slug instanceof App\Enums\RoleSlug` (`true`).

4. **Database State & Row Count Inspection:**
   - Executed read-only counts:
     - `users`: 0 rows.
     - `user_profiles`: 0 rows.
     - `user_addresses`: 0 rows.
     - `roles`: 0 rows.
     - `role_user`: 0 rows.
   - Confirmed that no records were created or modified during testing.

---

## 9. Database Safety

- **Zero Schema Alterations:** No columns, types, or indexes were modified in the MySQL `pesgo` database.
- **Zero Migrations Executed:** No migration commands were run.
- **Zero Test Records Inserted:** All casting verifications were performed purely in PHP memory without persisting records to MySQL.

---

## 10. Current Status

```text
Module 1 — Modul Pengurusan Pengguna
Phase 2 — Enums & Eloquent Casts
Status: COMPLETED
```

Phase 3 has **NOT** been implemented yet.

---

## 11. Next Phase

The planned next phase for Module 1 is:

> **Phase 3 — Role Foundation & Database Seeding**  
> Implementing `RoleSeeder` to populate the `roles` table with the approved roles (`customer`, `shopper`, `admin`) using `RoleSlug`, and establishing role helper methods on the `User` model.
