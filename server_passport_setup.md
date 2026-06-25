# Laravel Passport: Client Secret Cryptography & Server Configuration Guide

This guide explains how Laravel Passport client secrets are generated, hashed, verified, and how you should configure your server using the Tinker output you provided.

---

## 1. Server Environment Keys Configuration

Based on the Tinker output from your server:
- The **Password Grant Client ID** is: `019ed11f-3450-71d3-9e9e-95dd6c017e0f`
- The **Hashed Client Secret** in your database is: `"$2y$12$.8wXEw2jiCkI9u9O7bA8vu9oh3/2nTtwiNEXefHkelhhtsjZhS4Pu"`

Because the secret is stored as a secure one-way hash, you have **two options** to configure your server's `.env` file:

### Option A: The Development/Seeder Way (Recommended for matching dev setup)
In your database seeder, the secret is set to `null` to bypass client secret checking (treating the client as a public client). You can run this in your server's tinker shell to nullify the secret:

```php
DB::table('oauth_clients')
  ->where('id', '019ed11f-3450-71d3-9e9e-95dd6c017e0f')
  ->update(['secret' => null]);
```

Once that query is executed on the server, you can set the `.env` keys on the server to:
```env
PASSPORT_PASSWORD_CLIENT_ID=019ed11f-3450-71d3-9e9e-95dd6c017e0f
PASSPORT_PASSWORD_CLIENT_SECRET="any_dummy_string_or_same_as_local"
```
*(Since the database secret is `null`, Passport will bypass secret verification and accept any string).*

---

### Option B: The Strict Production Way (Most Secure)
If you want the secret to be validated strictly on the server, you cannot reuse the existing hashed secret because the plain-text value is lost. Instead, generate a new client on the server:

1. **Run the following command on the server**:
   ```bash
   php artisan passport:client --password --name="Quizlo Password Client" -n
   ```
2. **Retrieve the output**:
   ```text
   Client ID ............................. <NEW_UUID>
   Client Secret ..................... <NEW_PLAIN_TEXT_SECRET>
   ```
3. **Set the server `.env` keys** using the printed values:
   ```env
   PASSPORT_PASSWORD_CLIENT_ID=<NEW_UUID>
   PASSPORT_PASSWORD_CLIENT_SECRET="<NEW_PLAIN_TEXT_SECRET>"
   ```

---

## 2. The Cryptographic Algorithm ("The Whole Deal")

### How are the Secrets Generated?
When you run `php artisan passport:client --password`, Laravel Passport:
1. Generates a random, cryptographically secure 40-character string (using PHP's `random_bytes` function) to serve as the plain-text client secret.
2. Hashes that plain-text secret using the **bcrypt** algorithm.
3. Inserts the hashed secret into the `secret` column of the `oauth_clients` table.
4. Outputs the plain-text secret to your terminal. **It is never stored in plain-text anywhere in the system.**

### The Hashing Algorithm: Bcrypt
The secret hash `"$2y$12$.8wXEw2jiCkI9u9O7bA8vu9oh3/2nTtwiNEXefHkelhhtsjZhS4Pu"` uses the **bcrypt** hashing function:
- **`$2y$`**: Identifies the hashing algorithm variant (Blowfish variant used by PHP).
- **`12`**: Represents the **cost factor** ($2^{12} = 4096$ iterations). Higher cost factor increases the time required to compute the hash, making brute-force attacks infeasible.
- **The rest of the string**: The first 22 characters are the salt, and the remaining 31 characters are the computed hash.

### Why You Cannot Decrypt the Hash
Bcrypt is a **one-way cryptographic hash function**. Unlike symmetric encryption (like AES) which uses a key to decrypt text, hashing is a mathematical projection. You can hash a string to get its fingerprint, but you cannot mathematically reverse-engineer the fingerprint back to the original string.

### How Verification Works
When a user attempts to log in, the server receives the plain-text client secret (e.g. from `.env` or client request) and compares it with the database using PHP's `password_verify` or Laravel's `Hash::check`:

```php
Hash::check($plainTextSecretFromRequest, $hashedSecretFromDatabase)
```

1. Passport looks up the client by its ID in the `oauth_clients` table.
2. If the secret in the database is `null`, it assumes the client is **public** (non-confidential) and skips verification.
3. If the secret is not null, it hashes the incoming `$plainTextSecretFromRequest` using the same salt and cost factor, and checks if the resulting hash matches the one in the database.
