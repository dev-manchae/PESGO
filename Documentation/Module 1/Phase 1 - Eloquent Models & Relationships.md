# Module 1 — Phase 1: Eloquent Models & Relationships

**Project:** PESGO — Personal Shopper and Group Order  
**Module:** Module 1 — Modul Pengurusan Pengguna (User Management Module)  
**Phase:** Phase 1 — Eloquent Models & Relationships  
**Status:** COMPLETED  
**Framework:** Laravel 12.68.0 | PHP 8.2.12 | MySQL 8.0.31 (`pesgo`)  

---

## 1. Purpose

The purpose of Phase 1 is to establish the core **Eloquent ORM Model Foundation** for Module 1 (Modul Pengurusan Pengguna) in the PESGO backend application. 

Phase 1 directly maps the application's domain layer onto the existing, approved, and officially migrated MySQL 8.0 database schema. It establishes the PHP classes representing core identity, user profiles, address books, and role-based access control (RBAC), configuring their mass-assignment guards, attribute casts, serialization visibility, and bidirectional relational mappings.

---

## 2. Scope

### In Scope for Phase 1
Phase 1 covers exclusively the foundational Eloquent models and their relationships:
- **`User` Model:** The primary authentication root entity (`app/Models/User.php`).
- **`UserProfile` Model:** The personal and shopper identity entity (`app/Models/UserProfile.php`).
- **`UserAddress` Model:** The user address book entity (`app/Models/UserAddress.php`).
- **`Role` Model:** The system role definition entity (`app/Models/Role.php`).
- **Eloquent Relationships:** Defining `hasOne`, `hasMany`, `belongsTo`, and `belongsToMany` associations between entities.
- **Soft Delete Support:** Activating Laravel's `SoftDeletes` trait on entities with database-level `deleted_at` support.

### Out of Scope for Phase 1
The following concerns were intentionally **not implemented** in this phase and are deferred to subsequent phases:
- User registration and login workflows.
- API authentication, session handling, and Laravel Sanctum token management.
- PHP Backed Enums (`UserStatus`, `ShopperStatus`, `RoleSlug`) and custom Enum model casts (deferred to Phase 2).
- Role database seeding (`RoleSeeder`) and automatic role assignment (deferred to Phase 3).
- Application Services, Action classes, and Business Domain logic.
- HTTP Controllers, Form Requests, Input Validation rules, and API Routes.
- Authorization Policies, Gates, and Middleware.
- Personal Shopper onboarding and Admin approval workflows.
- Cascading soft-delete and account restoration lifecycle handlers.
- Future PESGO business modules (Group Order, Product Catalog, Payments, Shipments).

---

## 3. Database Tables Used

Phase 1 maps directly to five (5) existing tables in the `pesgo` database:

| Table Name | Entity | Relationship Type | Purpose in PESGO |
|---|---|---|---|
| `users` | `User` | Root Entity | Stores core authentication credentials (email, hashed password, display name, account status, timestamps, and soft delete timestamp). |
| `user_profiles` | `UserProfile` | 1:1 with `users` | Stores official legal identity (full name, phone number, identification number, bio, avatar, and shopper onboarding verification status). |
| `user_addresses` | `UserAddress` | 1:N with `users` | Stores shipping and billing addresses for consumers and shoppers, including geographic coordinates and default address flags. |
| `roles` | `Role` | Catalog Entity | Stores system role definitions (`customer`, `shopper`, `admin`). |
| `role_user` | Pivot Table | M:N (`users` ↔ `roles`) | Pivot table connecting users to multiple roles simultaneously, supporting dual-persona participation (Customer + Shopper). |

---

## 4. Models

### 4.1. User (`app/Models/User.php`)

The `User` model represents the central identity and security root of the PESGO system.

- **File Location:** `app/Models/User.php`
- **Database Table:** `users`
- **Inheritance:** `Illuminate\Foundation\Auth\User as Authenticatable`
- **Traits Used:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Notifications\Notifiable`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Mass-Assignable Attributes (`$fillable`):**
  - `name`: User display name / username.
  - `email`: Primary login credential (globally unique).
  - `password`: Bcrypt/Argon2id hashed password.
  - `status`: Account lifecycle state (`active`, `pending`, `suspended`, `deactivated`).
- **Hidden Attributes (`$hidden`):**
  - `password`: Excluded from JSON/array serialization.
  - `remember_token`: Excluded from JSON/array serialization.
- **Attribute Casts (`casts()`):**
  - `email_verified_at` → `datetime`
  - `password` → `hashed`
- **Relationships Defined:**
  - `profile()`: Defines a one-to-one relationship to `UserProfile`.
  - `addresses()`: Defines a one-to-many relationship to `UserAddress`.
  - `roles()`: Defines a many-to-many relationship to `Role` through `role_user`.

### 4.2. UserProfile (`app/Models/UserProfile.php`)

The `UserProfile` model stores official profile details and tracks Personal Shopper onboarding lifecycle data.

- **File Location:** `app/Models/UserProfile.php`
- **Database Table:** `user_profiles`
- **Inheritance:** `Illuminate\Database\Eloquent\Model`
- **Traits Used:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Mass-Assignable Attributes (`$fillable`):**
  - `user_id`: Foreign key reference to `users.id`.
  - `full_name`: Official full name as per identity documents.
  - `phone_number`: Contact phone number (unique, nullable).
  - `phone_verified_at`: Phone verification timestamp.
  - `identification_no`: National identification / passport number (unique, nullable for customers, mandatory for shoppers).
  - `avatar_url`: Profile photo URL.
  - `bio`: Short user description / shopper bio.
  - `shopper_status`: Personal shopper application state (`none`, `pending`, `approved`, `rejected`).
  - `shopper_verified_at`: Timestamp when shopper status was approved by admin.
- **Hidden Attributes (`$hidden`):**
  - `identification_no`: Hidden from default serialization to protect sensitive Personally Identifiable Information (PII).
- **Attribute Casts (`casts()`):**
  - `phone_verified_at` → `datetime`
  - `shopper_verified_at` → `datetime`
- **Relationships Defined:**
  - `user()`: Defines an inverse one-to-one / belongs-to relationship to `User`.

### 4.3. UserAddress (`app/Models/UserAddress.php`)

The `UserAddress` model manages delivery and billing destinations for PESGO orders.

- **File Location:** `app/Models/UserAddress.php`
- **Database Table:** `user_addresses`
- **Inheritance:** `Illuminate\Database\Eloquent\Model`
- **Traits Used:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Mass-Assignable Attributes (`$fillable`):**
  - `user_id`: Foreign key reference to `users.id`.
  - `label`: Address nickname/label (e.g., 'Rumah', 'Pejabat').
  - `recipient_name`: Recipient contact name.
  - `recipient_phone`: Recipient telephone number.
  - `address_line_1`: Primary street address line.
  - `address_line_2`: Secondary address details (unit, building, suite).
  - `postcode`: Postal code (indexed).
  - `city`: City / Municipality (indexed).
  - `state`: State / Province (indexed).
  - `country_code`: ISO 2-letter country code (default `'MY'`).
  - `latitude`: Geographic latitude coordinate (`decimal:8`).
  - `longitude`: Geographic longitude coordinate (`decimal:8`).
  - `is_default_shipping`: Boolean flag for primary delivery address.
  - `is_default_billing`: Boolean flag for primary billing address.
  - `delivery_instructions`: Special courier delivery notes.
- **Attribute Casts (`casts()`):**
  - `latitude` → `decimal:8`
  - `longitude` → `decimal:8`
  - `is_default_shipping` → `boolean`
  - `is_default_billing` → `boolean`
- **Relationships Defined:**
  - `user()`: Defines an inverse belongs-to relationship to `User`.

### 4.4. Role (`app/Models/Role.php`)

The `Role` model defines permissions and user capabilities within the PESGO RBAC structure.

- **File Location:** `app/Models/Role.php`
- **Database Table:** `roles`
- **Inheritance:** `Illuminate\Database\Eloquent\Model`
- **Traits Used:**
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
- **SoftDeletes Usage:**
  - **Not Used.** The physical `roles` table schema does not include a `deleted_at` column. Roles represent persistent, core system definitions that are not soft-deleted.
- **Mass-Assignable Attributes (`$fillable`):**
  - `name`: Human-readable role title (e.g., 'Customer', 'Personal Shopper', 'Administrator').
  - `slug`: System identifier (e.g., `'customer'`, `'shopper'`, `'admin'`).
  - `description`: Role responsibilities summary.
- **Relationships Defined:**
  - `users()`: Defines a many-to-many relationship to `User` through `role_user`.

---

## 5. Relationship Architecture

### Architecture Diagram

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

### Relationship Details

1. **User → UserProfile (`hasOne`)**
   - Method: `$user->profile()`
   - Foreign Key: `user_profiles.user_id`
   - Local Key: `users.id`
   - Cardinality: Exactly one profile per user (1:1).

2. **User → UserAddress (`hasMany`)**
   - Method: `$user->addresses()`
   - Foreign Key: `user_addresses.user_id`
   - Local Key: `users.id`
   - Cardinality: A user may have zero, one, or many addresses (1:N).

3. **User → Role (`belongsToMany`)**
   - Method: `$user->roles()`
   - Pivot Table: `role_user`
   - Foreign Pivot Key: `user_id`
   - Related Pivot Key: `role_id`
   - Pivot Data: Configured with `withPivot('created_at')`.  
     *(Note: The physical `role_user` schema possesses only a `created_at` timestamp; therefore `withTimestamps()` is not used to prevent SQL errors regarding missing `updated_at` columns).*

4. **UserProfile → User (`belongsTo`)**
   - Method: `$profile->user()`
   - Foreign Key: `user_profiles.user_id`
   - Owner Key: `users.id`

5. **UserAddress → User (`belongsTo`)**
   - Method: `$address->user()`
   - Foreign Key: `user_addresses.user_id`
   - Owner Key: `users.id`

6. **Role → User (`belongsToMany`)**
   - Method: `$role->users()`
   - Pivot Table: `role_user`
   - Foreign Pivot Key: `role_id`
   - Related Pivot Key: `user_id`
   - Pivot Data: Configured with `withPivot('created_at')`.

---

## 6. Soft Delete Strategy

### Active SoftDeletes Models
Soft delete support is enabled via the `Illuminate\Database\Eloquent\SoftDeletes` trait on:
- `User` (`users.deleted_at`)
- `UserProfile` (`user_profiles.deleted_at`)
- `UserAddress` (`user_addresses.deleted_at`)

### Current Behavior in Phase 1
When a model instance is deleted using `$model->delete()`, Eloquent executes an `UPDATE` statement setting `deleted_at = NOW()` instead of issuing a `DELETE FROM` query. All subsequent Eloquent queries automatically include `WHERE deleted_at IS NULL` unless explicitly bypassed via `withTrashed()` or `onlyTrashed()`.

### Lifecycle Cascading Status
> [!IMPORTANT]
> **Cascading Soft Deletes are NOT Implemented in Phase 1.**  
> In MySQL InnoDB, foreign key constraints configured with `ON DELETE CASCADE` execute **only during hard physical deletions** (`DELETE FROM users`). MySQL does not trigger foreign key cascades when a parent row is updated with a `deleted_at` timestamp.
>
> In Phase 1, model event listeners (e.g. `deleting` / `restoring` hooks) have intentionally **not been registered** on the `User` model. Synchronized cascading deletion and restoration of associated profiles and addresses are reserved for dedicated Domain Services in a later implementation phase.

---

## 7. Role Foundation

The Eloquent relationship established in Phase 1 supports the PESGO Role-Based Access Control (RBAC) requirements:

- **Supported Personas:**
  - `customer`: The default registered consumer/buyer.
  - `shopper`: A verified Personal Shopper capable of accepting purchase requests.
  - `admin`: Platform administrator.
- **Dual-Persona Coexistence:**
  A single user account (`users.id = 1`) can simultaneously possess both `customer` and `shopper` entries in `role_user`. A user does **not** create a second account to become a Personal Shopper.
- **Group Order Rule:**
  **Group Order is NOT a system role.** Group Order participation represents a business transaction and trip activity belonging to Module 2. It is not modeled as a role in Module 1.

---

## 8. Model Configuration Reference

| Model | Table | SoftDeletes | Primary Key | Mass-Assignable (`$fillable`) | Hidden (`$hidden`) | Casts (`casts()`) |
|---|---|---|---|---|---|---|
| **`User`** | `users` | Yes | `id` (BigInt Auto) | `name`, `email`, `password`, `status` | `password`, `remember_token` | `email_verified_at` (datetime), `password` (hashed) |
| **`UserProfile`** | `user_profiles` | Yes | `id` (BigInt Auto) | `user_id`, `full_name`, `phone_number`, `phone_verified_at`, `identification_no`, `avatar_url`, `bio`, `shopper_status`, `shopper_verified_at` | `identification_no` | `phone_verified_at` (datetime), `shopper_verified_at` (datetime) |
| **`UserAddress`** | `user_addresses` | Yes | `id` (BigInt Auto) | `user_id`, `label`, `recipient_name`, `recipient_phone`, `address_line_1`, `address_line_2`, `postcode`, `city`, `state`, `country_code`, `latitude`, `longitude`, `is_default_shipping`, `is_default_billing`, `delivery_instructions` | None | `latitude` (decimal:8), `longitude` (decimal:8), `is_default_shipping` (boolean), `is_default_billing` (boolean) |
| **`Role`** | `roles` | No | `id` (BigInt Auto) | `name`, `slug`, `description` | None | None |

---

## 9. Verification Performed

During the completion of Phase 1, the following non-destructive verification checks were performed:

1. **PHP CLI Syntax Validation (`php -l`):**
   - `User.php`: Passed with 0 syntax errors.
   - `UserProfile.php`: Passed with 0 syntax errors.
   - `UserAddress.php`: Passed with 0 syntax errors.
   - `Role.php`: Passed with 0 syntax errors.

2. **Eloquent Relationship Reflection Check (via `php artisan tinker`):**
   - Verified that calling `$user->profile()` instantiates `Illuminate\Database\Eloquent\Relations\HasOne` targeting `user_id`.
   - Verified that calling `$user->addresses()` instantiates `Illuminate\Database\Eloquent\Relations\HasMany` targeting `user_id`.
   - Verified that calling `$user->roles()` instantiates `Illuminate\Database\Eloquent\Relations\BelongsToMany` referencing table `role_user` with `user_id` and `role_id`.
   - Verified that calling `$profile->user()` instantiates `Illuminate\Database\Eloquent\Relations\BelongsTo` referencing `user_id`.
   - Verified that calling `$address->user()` instantiates `Illuminate\Database\Eloquent\Relations\BelongsTo` referencing `user_id`.
   - Verified that calling `$role->users()` instantiates `Illuminate\Database\Eloquent\Relations\BelongsToMany` referencing table `role_user` with `role_id` and `user_id`.

3. **SoftDeletes Trait Verification:**
   - Evaluated `class_uses_recursive()` on instances of all 4 models:
     - `User`: SoftDeletes confirmed active (`YES`).
     - `UserProfile`: SoftDeletes confirmed active (`YES`).
     - `UserAddress`: SoftDeletes confirmed active (`YES`).
     - `Role`: SoftDeletes confirmed absent (`NO`).

4. **Database State & Row Count Inspection:**
   - Executed read-only table counts across all relevant tables:
     - `users`: 0 rows.
     - `user_profiles`: 0 rows.
     - `user_addresses`: 0 rows.
     - `roles`: 0 rows.
     - `role_user`: 0 rows.
   - Confirmed that no data was inserted, altered, or deleted during model verification.

---

## 10. Database Safety

- **Zero Schema Alterations:** No database tables, columns, constraints, or indexes were added, altered, or dropped.
- **Zero Migration Execution:** Neither `php artisan migrate`, `php artisan migrate:fresh`, nor `php artisan migrate:rollback` were executed.
- **Database Preserved:** The MySQL database `pesgo` remains in its verified, clean post-migration state with 13 tables and 0 business records.

---

## 11. Current Status

```
Module 1 — Modul Pengurusan Pengguna
Phase 1 — Eloquent Models & Relationships
Status: COMPLETED
```

Phase 2 has **NOT** been implemented yet.

---

## 12. Next Phase

The planned next phase for Module 1 is:

> **Phase 2 — Enums & Eloquent Casts**  
> Defining PHP 8.2 Backed Enums (`UserStatus`, `ShopperStatus`, `RoleSlug`) and attaching them via model casts on `User` and `UserProfile`.
