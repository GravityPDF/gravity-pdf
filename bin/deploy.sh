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
echo "Begin Tag Deployment"
svn --no-auth-cache --non-interactive --username "$WP_ORG_USERNAME" --password "$WP_ORG_PASSWORD" mkdir "https://plugins.svn.wordpress.org/$PLUGIN/tags/$TRAVIS_TAG" -m "Create tag $TRAVIS_TAG"

cd "$PLUGIN_BUILDS_PATH"

# Checkout the SVN tag
svn co -q  "https://plugins.svn.wordpress.org/$PLUGIN/tags/$TRAVIS_TAG" svn

# Add new version tag
rsync -r -p $TRAVIS_TAG/* svn

# Add new files to SVN
svn stat svn | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
# Remove deleted files from SVN
svn stat svn | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@
svn stat svn

# Commit to SVN
svn ci --no-auth-cache --non-interactive --username "$WP_ORG_USERNAME" --password "$WP_ORG_PASSWORD" svn -m "Deploy version $TRAVIS_TAG"

# Remove SVN temp dir
rm -fR svn

# Create Trunk
echo "End Tag Deployment"
echo "Begin Trunk Deployment"
svn co -q "http://svn.wp-plugins.org/$PLUGIN/trunk" svn

# Move out the trunk directory to a temp location
mv svn svn-trunk

# Copy our new version of the plugin into trunk
rsync -r -p $TRAVIS_TAG/* svn

# Remove the README.txt file from the plugin, and back in the copied version
cp svn-trunk/README.txt svn

# Copy all the .svn folders from the checked out copy of trunk to the new trunk.
# This is necessary as the Travis container runs Subversion 1.6 which has .svn dirs in every sub dir
TARGET="$PLUGIN_BUILDS_PATH/svn"
cd "$PLUGIN_BUILDS_PATH/svn-trunk"

# Find all .svn dirs in sub dirs
SVN_DIRS=`find . -type d -iname .svn`

for SVN_DIR in $SVN_DIRS; do
    SOURCE_DIR=${SVN_DIR/.}
    TARGET_DIR=$TARGET${SOURCE_DIR/.svn}
    TARGET_SVN_DIR=$TARGET${SVN_DIR/.}
    if [ -d "$TARGET_DIR" ]; then
        # Copy the .svn directory to trunk dir
        cp -r $SVN_DIR $TARGET_SVN_DIR
    fi
done

# Back to builds dir
cd "$PLUGIN_BUILDS_PATH"

# Remove checked out dir
rm -fR svn-trunk

# Add new files to SVN
svn stat svn | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
# Remove deleted files from SVN
svn stat svn | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@
svn stat svn

# Commit to SVN
svn ci --no-auth-cache --non-interactive --username "$WP_ORG_USERNAME" --password "$WP_ORG_PASSWORD" svn -m "Deploy trunk for $TRAVIS_TAG"

# Remove SVN temp dir
rm -fR svn

echo "End Trunk Deployment"