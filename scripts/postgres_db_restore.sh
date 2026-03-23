#!/bin/bash
echo "Restoring database..."
docker exec -i news_db psql -U bob_bloom -d news_rag < ./news_rag_backup.sql
echo "Done!"


# This file must be executible. If it is not, run this:
# chmod +x scripts/postgres_db_restore.sh