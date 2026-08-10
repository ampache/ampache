#!/bin/bash

# This script checks for updates to the Ampache Docker container and updates it if necessary.
# If the container is updated, run commands to update the database, config file, and plugins.

COMPOSE_DIR="/var/www/ampache"
SERVICE_NAME="ampache"

cd "$COMPOSE_DIR" || { echo "Directory not found: $COMPOSE_DIR"; exit 1; }

docker compose pull "$SERVICE_NAME"

# Get the current running container ID
CONTAINER_ID=$(docker compose ps -q "$SERVICE_NAME")

# If the container isn't running, exit silently
if [ -z "$CONTAINER_ID" ]; then
  echo "No running container for $SERVICE_NAME."
  exit 0
fi

# Get the image ID currently in use by the running container
CURRENT_IMAGE_ID=$(docker inspect --format='{{.Image}}' "$CONTAINER_ID")

# Get the image name from compose config
IMAGE_NAME=$(docker compose config | awk "/image:/ && /$SERVICE_NAME/ {print \$2}")

# Get the latest image ID
LATEST_IMAGE_ID=$(docker inspect --format='{{.Id}}' "$IMAGE_NAME")

# Compare image IDs
if [ "$CURRENT_IMAGE_ID" != "$LATEST_IMAGE_ID" ]; then
  echo "Image updated. Stopping and removing containers..."
  docker compose down
  docker compose up -d
  sleep 5
  docker exec $SERVICE_NAME /var/www/bin/cli admin:updateDatabase -e
  docker exec $SERVICE_NAME /var/www/bin/cli admin:updateConfigFile -e
  docker exec $SERVICE_NAME /var/www/bin/cli admin:updatePlugins -e
fi