# Fix: Live Site 404 After Login

## Problem
The live site shows a 404 after logging in because `auth.php` queries for a `first_name` column that doesn't exist in the live database.

## Solution
Run the following SQL migration on the live database.

### Steps

1. Log into BigScoots cPanel
2. Open phpMyAdmin
3. Select the `paulgreb_adminability` database
4. Go to the **SQL** tab
5. Paste and run this SQL:

```sql
-- Add first_name column
ALTER TABLE users ADD COLUMN first_name VARCHAR(50) AFTER name;

-- Populate from existing name (everything before first space)
UPDATE users SET first_name = SUBSTRING_INDEX(name, ' ', 1);

-- Make it NOT NULL after populating
ALTER TABLE users MODIFY first_name VARCHAR(50) NOT NULL;
```

6. Click **Go** to execute
7. Test login at https://adminability.ac/TreePlane

## Why This Happened
The `auth.php` file was updated to include `first_name` in the user query, but the database migration wasn't run on the live server. The query fails silently, causing authentication to fail.
