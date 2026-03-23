#!/bin/bash
echo "Dumping database..."
docker exec news_db pg_dump -U bob_bloom -d news_rag > ./news_rag_backup.sql
echo "Done! Backup saved to news_rag_backup.sql"


# This file must be executible. If it is not, run this:
# chmod +x scripts/postgres_db_dump.sh