#!/usr/bin/env bash

set -ex

if [ -z "$1" ]; then
    echo "Please specify the Symfony version to install"
    exit 1
fi

echo "Installing Symfony version $1"

# This is not required for CI, but it allows to test the script locally
function cleanup {
    echo "Cleaning up"
    # Restore the original composer.json file
    mv composer.origin.json composer.json
}

function install-specified-symfony-version {
    local symfony_version=$1
    # Save the original composer.json file
    cp composer.json composer.origin.json
    # Delete the lock file and vendor directory for a clean install
    rm composer.lock || true
    rm -Rf vendor || true
    # Replace the Symfony version in composer.json
    sed -i 's/\^5.4 || \^6.0 || \^7.0/'$symfony_version'/g' composer.json
    # Install the specified Symfony version
    composer install
}

# Ensure cleanup is called on exit
trap cleanup EXIT

install-specified-symfony-version $1
