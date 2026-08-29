# PESGO — Module 1 — Phase 3
## Role Foundation & Database Seeding

**Project:** PESGO — Personal Shopper and Group Order  
**Module:** Module 1 — Modul Pengurusan Pengguna (User Management Module)  
**Phase:** Phase 3 — Role Foundation & Database Seeding  
**Status:** COMPLETED  
**Framework:** Laravel 12.68.0 | PHP 8.2.12 | MySQL 8.0.31 (`pesgo`)  
**Previous Phase:** `Phase 2 - Enums & Eloquent Casts.md`  

---

## 1. Phase Overview

Phase 3 establishes the initial **Role-Based Access Control (RBAC) Foundation** and **Database Seeding Layer** for Module 1 in the PESGO application.

Building directly upon the Eloquent models established in Phase 1 and the PHP 8.2 string-backed enums delivered in Phase 2, Phase 3 populates the physical database with PESGO's canonical system roles and equips the `User` model with expressive, type-safe, and cache-aware role checking, assignment, and removal helpers.

---

## 2. Objectives

1. **Seed Canonical System Roles:** Populate the physical `roles` table with the three core domain roles (`admin`, `shopper`, `customer`) via an idempotent seeder.
2. **Cleanse Baseline Database Seeder:** Integrate `RoleSeeder` into `DatabaseSeeder` while eliminating unneeded Laravel default dummy factory users.
3. **Implement Eloquent RBAC Helpers:** Add convenience methods to `User.php` supporting dual persona capabilities (`customer` and `shopper` simultaneously).
4. **Enforce Type & Cache Integrity:** Support both backed enum instances and raw strings interchangeably, prevent SQL duplicate key collisions, and invalidate stale in-memory relationship collections upon role mutation.
5. **Preserve Scope Boundaries:** Maintain strict isolation from future phases (zero Sanctum tokens, zero authentication endpoints, zero Group Order functionality).

---

## 3. Scope

### In Scope for Phase 3
* **`RoleSeeder` (`database/seeders/RoleSeeder.php`):** Idempotent database seeder creating the 3 canonical roles using `RoleSlug`.
* **`DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`):** Master seeder invoking `RoleSeeder` and removing mock user generation.
* **`User` RBAC Helpers (`app/Models/User.php`):**
  * `hasRole(RoleSlug|string $role): bool`
  * `hasAnyRole(array $roles): bool`
  * `isAdmin(): bool`
  * `isShopper(): bool`
  * `isCustomer(): bool`
  * `assignRole(RoleSlug|string|Role $role): void`
  * `removeRole(RoleSlug|string|Role $role): void`
* **Verification & Testing:** Non-destructive testing confirming role population, idempotency, dual-persona co-existence, relationship cache invalidation, and zero data pollution.

### Out of Scope / Explicitly Deferred
The following concerns were intentionally **not implemented** in Phase 3 and are deferred to subsequent phases:
* **Authentication & Tokens (Phase 4):** Laravel Sanctum installation (`php artisan install:api`), personal access tokens, login/logout session handling.
* **Registration & Validation Services (Phase 5):** Registration workflows, Form Requests, `AuthService`, `ProfileService`, `AddressService`.
* **HTTP Layer (Phase 6):** Controllers, API routes (`routes/api.php`), API Resource responses.
* **Authorization Policies & Middleware (Phase 7):** Role-checking middleware, Laravel Gates, and Policies (`AddressPolicy`, `ProfilePolicy`).
* **Automated Feature Integration Tests (Phase 8):** Comprehensive HTTP feature test suite.
* **Personal Shopper Verification Workflow:** Document upload, IC verification, and admin approval workflows (handled in profile domain).
* **Group Order Features:** Group order catalogs, pools, and split-payment transactions (handled strictly in Module 2).

---

## 4. Role Architecture

PESGO implements a normalized, relational Many-to-Many RBAC structure across three tables:

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

### Key Architectural Principles:
1. **1 Real Person = 1 PESGO Account:** A user maintains a single record in `users`.
2. **Dual Persona Support:** A user can act as both a **Customer** (buyer) and a **Personal Shopper** (runner) simultaneously without registering multiple accounts. This is enabled by the composite primary key `(user_id, role_id)` on `role_user`.
3. **Group Order Is Not a Role:** Group Order participation is an activity/transaction in Module 2, not a system authorization role. There is no `group_order` role.

---

## 5. RoleSlug Enum

The canonical representation of role identifiers in PESGO is the `App\Enums\RoleSlug` string-backed enum:

```php
namespace App\Enums;

enum RoleSlug: string
{
    case CUSTOMER = 'customer';
    case SHOPPER = 'shopper';
    case ADMIN = 'admin';
}
```

* **File Location:** `app/Enums/RoleSlug.php`
* **Backing Type:** `string`
* **Cases:**
  * `CUSTOMER ('customer')`: Baseline consumer role; enabled for all registered buyers.
  * `SHOPPER ('shopper')`: Verified personal shopper persona with runner capabilities.
  * `ADMIN ('admin')`: Platform administrator with management oversight.

---

## 6. Role Model Integration

The `Role` model (`app/Models/Role.php`) directly maps to the `roles` table:

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
> **No SoftDeletes on Roles:** As verified in the database schema, the `roles` table does not contain a `deleted_at` column. `Role.php` intentionally omits the `SoftDeletes` trait, preserving strict alignment with the physical schema.

---

## 7. RoleSeeder

The `RoleSeeder` class (`database/seeders/RoleSeeder.php`) seeds the three canonical roles idempotently:

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

### Architectural Decisions:
* **`updateOrCreate()`:** Keys on `['slug' => $role['slug']]`. If a role exists, its name and description are refreshed; if it does not exist, it is inserted. This prevents `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry` on the `slug` and `name` unique indexes.
* **Safe Repeated Execution:** The seeder can be run safely in initial setups, automated test scripts, and deployment pipelines.

---

## 8. DatabaseSeeder

The master `DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`) delegates directly to `RoleSeeder`:

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

### Key Changes Made:
* Removed default Laravel boilerplate that called `User::factory()->create(...)`.
* Running `php artisan db:seed` now creates exactly the three canonical roles and leaves the `users` table completely clean (0 rows).

---

## 9. User RBAC Helpers

Seven helper methods were implemented directly on `App\Models\User`:

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

## 10. Role Assignment and Removal

The role mutation methods guarantee idempotency and memory consistency:

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

### Critical Implementation Details:
1. **`syncWithoutDetaching()` Over `attach()`:** Because `role_user` has a composite primary key `(user_id, role_id)`, calling `attach()` on an already-assigned role triggers a fatal SQL error `1062 Duplicate entry`. `syncWithoutDetaching()` ensures assigning an existing role succeeds silently and idempotently.
2. **`detach()` on Removal:** Safely deletes the pivot entry. If the role was not assigned, it executes without error.

---

## 11. Dual Persona Support

In PESGO's business model, a user can simultaneously act as a buyer and a shopper. The implementation natively guarantees this:

```php
$user->assignRole(RoleSlug::CUSTOMER);
$user->assignRole(RoleSlug::SHOPPER);

$user->isCustomer(); // Returns: true
$user->isShopper();  // Returns: true
```

* Neither `isCustomer()` nor `isShopper()` uses mutually exclusive logic (`if/else`).
* Removing the shopper persona (`$user->removeRole(RoleSlug::SHOPPER)`) sets `isShopper()` to `false` while `isCustomer()` remains `true`.

---

## 12. Relationship Loading & Cache Handling

To prevent both the **N+1 Query Problem** and **Stale In-Memory Cache Bugs**, `User.php` employs dual-mode checking and cache eviction:

### A. Dual-Mode Evaluation in `hasRole()`
* **When `roles` Relation Is Loaded (`$this->relationLoaded('roles')`):**  
  `hasRole()` evaluates the in-memory Eloquent Collection using `$this->roles->contains(...)`. Zero SQL queries are fired.
* **When `roles` Relation Is Not Loaded:**  
  `hasRole()` executes a lightweight indexed SQL query:  
  `$this->roles()->where('slug', $roleSlug->value)->exists();`

### B. In-Memory Cache Eviction via `unsetRelation('roles')`
When `assignRole()` or `removeRole()` alters the database pivot table, calling `$this->unsetRelation('roles')` purges the cached collection on the model instance. Any subsequent call to `$user->roles` or `hasRole()` fetches the refreshed state from the database.

---

## 13. Invalid Role Handling

The implementation distinguishes deliberately between querying and mutating roles:

| Method | Input | Behavior | Rationale |
|---|---|---|---|
| `hasRole()` | Unrecognized string (e.g. `'superadmin'`, `'group_order'`) | Returns `false` safely | A user cannot possess a non-existent role; queries return boolean without raising exceptions. |
| `assignRole()` | Non-existent role string or invalid enum | Throws `InvalidArgumentException` | Prevents corrupting the database or attempting to attach a null role ID. |
| `removeRole()` | Role not possessed by user | Completes safely without error | Idempotent detachment; no-op if the relation does not exist. |

---

## 14. Database Verification

A direct inspection of the MySQL `pesgo` database confirmed:

```sql
SELECT id, slug, name, description FROM pesgo.roles ORDER BY id;
```

| `id` | `slug` | `name` | `description` | Backed Enum Cast |
|:---:|:---|:---|:---|:---|
| **1** | `admin` | Administrator | Pengurus sistem PESGO dan pemantau transaksi | `RoleSlug::ADMIN` |
| **2** | `shopper` | Personal Shopper | Penyedia servis belian peribadi dan pesanan kumpulan | `RoleSlug::SHOPPER` |
| **3** | `customer` | Customer | Pengguna pembeli biasa dan penyertai pesanan kumpulan | `RoleSlug::CUSTOMER` |

### Database State Counts:
* **`roles` Table:** Exactly **3** records.
* **`group_order` Check:** **0** records match `slug LIKE '%group%'`.
* **`role_user` Table:** **0** rows (clean).
* **`users` Table:** **0** rows (clean).
* **`migrations` Table:** **7** migrations (all Batch 1).

---

## 15. Verification Tests

All verification procedures were performed safely without altering the database schema or leaving mock data behind:

1. **PHP CLI Syntax Validation (`php -l`):**
   * `backend/app/Models/User.php`: Passed (0 errors).
   * `backend/database/seeders/RoleSeeder.php`: Passed (0 errors).
   * `backend/database/seeders/DatabaseSeeder.php`: Passed (0 errors).
2. **Seeder Execution & Idempotency:**
   * Executed `php artisan db:seed --class=RoleSeeder` (0 errors).
   * Executed `php artisan db:seed` (0 errors). Total role count remained exactly 3.
3. **Automated Transactional Suite (`DB::beginTransaction()` / `DB::rollBack()`):**
   * Verified initial states: `isCustomer()`, `isShopper()`, `isAdmin()` all `false`.
   * Verified `assignRole(RoleSlug::CUSTOMER)` sets `isCustomer()` to `true`.
   * Verified subsequent `assignRole(RoleSlug::SHOPPER)` sets `isShopper()` to `true` while `isCustomer()` remains `true`.
   * Verified `hasRole('customer')` (string) and `hasRole(RoleSlug::CUSTOMER)` (enum) resolve identically.
   * Verified in-memory collection checking when `$user->load('roles')` is active.
   * Verified duplicate `assignRole()` calls execute without MySQL 1062 errors.
   * Verified `hasAnyRole()` matches accurately.
   * Verified `hasRole('non_existent')` returns `false`.
   * Verified `removeRole(RoleSlug::SHOPPER)` removes the shopper persona while preserving customer status.
   * Verified `assignRole(RoleSlug::ADMIN)` and `removeRole('admin')`.
   * Transaction rolled back cleanly: `User::count()` confirmed at 0.

---

## 16. Security & Integrity Considerations

1. **Mass-Assignment Protection:** Roles are assigned exclusively through the `roles()` relationship; `$roles` is not present in `$fillable`, preventing parameter injection via request bodies.
2. **Strict Foreign Key Cascades:** Database constraints `ON DELETE CASCADE ON UPDATE CASCADE` ensure that if a user is hard-deleted, their pivot entries in `role_user` are purged automatically.
3. **No Hardcoded Admin Credentials:** `RoleSeeder` creates the role definition only; it does not create a default administrator user with a hardcoded password.
4. **Decoupling of Shopper Status:** The system authorization role `shopper` in `role_user` is distinct from the onboarding lifecycle flag `user_profiles.shopper_status`. The role helper `isShopper()` inspects the RBAC tables, keeping authorization clean.

---

## 17. Deferred Functionality

To maintain strict architectural boundaries, the following components were deferred:

* **Laravel Sanctum API Tokens:** Scheduled for Phase 4.
* **Authentication Flows (Register, Login, Password Reset):** Scheduled for Phase 4 & Phase 5.
* **Authorization Policies & Route Middleware:** Scheduled for Phase 7.
* **Group Order Features:** Reserved for Module 2.

---

## 18. Files Changed

Only three files were created or modified for Phase 3:

| File | Change Type | Description |
|---|:---:|---|
| [`backend/database/seeders/RoleSeeder.php`](file:///d:/PESGO/backend/database/seeders/RoleSeeder.php) | **NEW** | Idempotent seeder populating `admin`, `shopper`, and `customer` roles. |
| [`backend/database/seeders/DatabaseSeeder.php`](file:///d:/PESGO/backend/database/seeders/DatabaseSeeder.php) | **MODIFIED** | Configured `$this->call([RoleSeeder::class]);` and removed demo user factory. |
| [`backend/app/Models/User.php`](file:///d:/PESGO/backend/app/Models/User.php) | **MODIFIED** | Added 7 RBAC helper methods with dual-persona support and cache eviction. |

---

## 19. Final Phase Status

```text
Module 1 — Modul Pengurusan Pengguna
Phase 3 — Role Foundation & Database Seeding
Status: COMPLETED (Audit Approved)
```

The role foundation is fully operational, verified, and documented. The codebase is ready for **Phase 4: API Authentication Foundation & Laravel Sanctum Setup**.
