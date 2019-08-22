#!/bin/bash

# Include useful environmentals / functions
. "$(dirname "$0")/env.sh"
. "$(dirname "$0")/functions.sh"

# Check that Composer is installed.
if ! command_exists "composer"; then
	echo -e $(error_message "Composer doesn't seem to be installed. Please head on over to the Composer site to download it: $(action_format "https://getcomposer.org/download/")")
	exit 1
fi

# Check that Yarn is installed.
if ! command_exists "yarn"; then
	echo -e $(error_message "Yarn doesn't seem to be installed. Please head on over to the Yarn site to download it: $(action_format "https://yarnpkg.com/en/docs/install")")
	exit 1
fi

echo -e $(status_message "Building Gravity PDF...")
composer install