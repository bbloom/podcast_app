# Database Restore Procedure

This document describes how to restore the database from a backup file stored in S3.
Run through this procedure manually every few months to verify that backups are actually restorable.

---

## What the backup files contain

Each backup is a **plain-SQL pg_dump**, gzip-compressed.

- Format: `{prefix}_{YYYY-MM-DD_HH-MM-SS}.sql.gz`
- Example: `newsrag_2026-03-18_03-00-00.sql.gz`
- Location: `s3://{BACKUP_S3_BUCKET}/{BACKUP_S3_FOLDER}/`

A plain-SQL dump is restored with `psql`, not `pg_restore`.

---

## Step 1 — Download the backup from S3

Use the AWS CLI (or the S3 console):

```bash
aws s3 cp s3://YOUR_BUCKET/backups/newsrag_2026-03-18_03-00-00.sql.gz ./restore/
```

---

## Step 2 — Verify the file before restoring

Check that the file is not corrupt before you attempt a restore:

```bash
gunzip -t restore/newsrag_2026-03-18_03-00-00.sql.gz
```

No output = file is intact. Any error = file is corrupt; use a different backup.

---

## Step 3 — Restore

### Option A — Restore into a throwaway database (recommended for testing)

```bash
# Create a fresh test database
createdb -U bob_bloom news_rag_restore_test

# Decompress and pipe into psql
gunzip -c restore/newsrag_2026-03-18_03-00-00.sql.gz \
    | psql -h 127.0.0.1 -U bob_bloom -d news_rag_restore_test
```

Then run a few sanity queries:

```sql
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM summaries;
SELECT MAX(created_at) FROM summaries;
```

Drop the test database when done:

```bash
dropdb -U bob_bloom news_rag_restore_test
```

### Option B — Full production restore (disaster recovery)

> ⚠️ This destroys all current data. Only proceed if you have confirmed the production database is unrecoverable.

```bash
# Drop and recreate the database
dropdb -U bob_bloom news_rag
createdb -U bob_bloom news_rag

# Restore
gunzip -c restore/newsrag_2026-03-18_03-00-00.sql.gz \
    | psql -h 127.0.0.1 -U bob_bloom -d news_rag
```

---

## Periodic restore test checklist

Run Option A every 1–3 months:

- [ ] Downloaded a recent backup from S3
- [ ] `gunzip -t` passed (no errors)
- [ ] Restore completed without errors
- [ ] Row counts look plausible (`users`, `summaries`, `lists`)
- [ ] Most recent `summaries.created_at` matches expected last-run date
- [ ] Test database dropped