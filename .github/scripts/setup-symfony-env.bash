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
    mv composer.origin.json composer.json
}

function install-specified-symfony-version {
    local symfony_version=$1
    cp composer.json composer.origin.json
    rm composer.lock || true
    rm -Rf vendor || true
    sed -i 's/\^5.4 || \^6.0 || \^7.0/\^'$symfony_version'/g' composer.json
    composer install
}

# Ensure cleanup is called on exit. Handles both success and failure exits
trap cleanup EXIT

install-specified-symfony-version $1
