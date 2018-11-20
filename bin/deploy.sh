#!/usr/bin/env bash

if [[ -z "$TRAVIS" ]]; then
	echo "Script is only to be run by Travis CI" 1>&2
	exit 1
fi

if [[ -z "$WP_ORG_PASSWORD" ]]; then
	echo "WordPress.org password not set" 1>&2
	exit 1
fi

PROJECT_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
PLUGIN_BUILDS_PATH="$PROJECT_ROOT/tmp/package"

# Ensure the current build directory exists
if [ ! -d "$PLUGIN_BUILDS_PATH/$TRAVIS_TAG" ]; then
    echo "Built directory $PLUGIN_BUILDS_PATH/$TRAVIS_TAG does not exist" 1>&2
    exit 1
fi

# Check if the tag exists for the version we are building
TAG=$(svn ls "https://plugins.svn.wordpress.org/$PLUGIN/tags/$TRAVIS_TAG")
error=$?
if [ $error == 0 ]; then
    # Tag exists, don't deploy
    echo "Tag already exists for version $TRAVIS_TAG, aborting deployment"
    exit 1
fi

# Create Tags
echo "$WP_ORG_USERNAME"
echo "$WP_ORG_PASSWORD"
exit 1

cd "$PLUGIN_BUILDS_PATH"

# Checkout the SVN repo
svn co -q "http://svn.wp-plugins.org/$PLUGIN" svn

# Add new version tag
mkdir svn/tags/$TRAVIS_TAG
rsync -r -p $TRAVIS_TAG/* svn/tags/$TRAVIS_TAG

# Add new files to SVN
svn stat svn | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
# Remove deleted files from SVN
svn stat svn | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@
svn stat svn

# Commit to SVN
svn ci --no-auth-cache --non-interactive --username "$WP_ORG_USERNAME" --password "$WP_ORG_PASSWORD" svn -m "Deploy version $TRAVIS_TAG"

# Remove SVN temp dir
rm -fR svn