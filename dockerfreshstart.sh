#!/bin/bash
set -e  # stops this script when encounters an error


echo "Step 1: Stop all containers..."
docker stop $(docker ps -aq) || true

echo "Step 2: Remove all containers..."
docker rm -f $(docker ps -aq) || true

echo "Step 3: Remove all images..."
docker rmi -f $(docker images -q) || true

echo "Step 4: Remove all user-defined networks..."
docker network rm $(docker network ls -q --filter "type=custom") || true

echo "=== Step 5: Removing all volumes (including Postgres) ==="
docker volume rm $(docker volume ls -q) || true

echo "=== Docker full cleanup complete! ==="
echo "Your Postgres database backup remains at: $BACKUP_FILE"
echo "You can restore it after spinning up a fresh container."

# after clean up, verify
echo "Running commands to verify that Docker is removed. All should be empty:";
docker ps -a        # should be empty
docker images       # should be empty
docker volume ls    # should be empty
docker network ls   # only defaults remain (bridge, host, none)
