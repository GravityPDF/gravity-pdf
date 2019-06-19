#!/bin/bash

# Include useful environmentals / functions
. "$(dirname "$0")/env.sh"
. "$(dirname "$0")/functions.sh"

# Wait until the Docker containers are running and the WordPress site is responding to requests.
echo -e $(status_message "Waiting for the Docker WordPress node to start...")

until is_wordpress "http://$HOST_IP:$HOST_PORT"; do
    if is_wordpress "http://$MACHINE_IP:$HOST_PORT"; then
        HOST_IP=$MACHINE_IP
    fi

    echo -n '.'
    sleep 5
done
echo ''

HOST_URL="http://$HOST_IP:$HOST_PORT"

# Save HOST to .env file
if [ -f ".env" ] && [ -z "$E2E_TESTING_URL" ]; then
    echo -e "\nE2E_TESTING_URL=$HOST_URL" >> .env
fi

# Install WordPress.
echo -e $(status_message "Installing WordPress...")

# The `-u 33` flag tells Docker to run the command as a particular user and
# prevents permissions errors. See: https://github.com/WordPress/gutenberg/pull/8427#issuecomment-410232369
docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI db reset --yes --quiet
docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI core install --title="$SITE_TITLE" --admin_user=admin --admin_password=password --admin_email=test@test.com --skip-email --url=$HOST_URL --quiet

# Make sure the uploads and upgrade folders exist and we have permissions to add files.
echo -e $(status_message "Ensuring that files can be uploaded...")
docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm $CONTAINER mkdir -p /var/www/html/wp-content/uploads /var/www/html/wp-content/upgrade

# Check the WordPress URL matches the current access URL
echo -e $(status_message "Checking the site's url...")
CURRENT_URL=$(docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run -T --rm $CLI option get siteurl)
if [ "$CURRENT_URL" != "$HOST_URL" ]; then
	docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI option update home "$HOST_URL" --quiet
	docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI option update siteurl "$HOST_URL" --quiet
fi

# Configure site constants.
echo -e $(status_message "Configuring site constants...")
docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI config set WP_DEBUG $WP_DEBUG --raw --type=constant --quiet
WP_DEBUG_RESULT=$(docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run -T --rm -u 33 $CLI config get --type=constant --format=json WP_DEBUG)
echo -e $(status_message "WP_DEBUG: $WP_DEBUG_RESULT...")

docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI config set SCRIPT_DEBUG $SCRIPT_DEBUG --raw --type=constant --quiet
SCRIPT_DEBUG_RESULT=$(docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run -T --rm -u 33 $CLI config get --type=constant --format=json SCRIPT_DEBUG)
echo -e $(status_message "SCRIPT_DEBUG: $SCRIPT_DEBUG_RESULT...")

# Setup additional users
docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI user create editor editor@test.com --role=editor --user_pass=password --quiet

# Setup the Plugins
. "$(dirname "$0")/setup-plugins.sh"
. "$(dirname "$0")/setup-gf-data.sh"

echo -e $(status_message "WordPress is setup and accessible at $(action_format "$HOST_URL")")
echo -e $(status_message "Access $(action_format "$HOST_URL/wp-admin/") using the following credentials:")
echo -e $(status_message "Default username: $(action_format "admin"), password: $(action_format "password")")