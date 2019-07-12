#!/bin/bash

# Include useful environmentals / functions
. "$(dirname "$0")/env.sh"
. "$(dirname "$0")/functions.sh"

# Check Docker is installed and running
. "$(dirname "$0")/setup-docker.sh"

# Build Gravity PDF
. "$(dirname "$0")/setup-gravitypdf.sh"

# Set up WordPress Development site.
. "$(dirname "$0")/setup-wordpress.sh"