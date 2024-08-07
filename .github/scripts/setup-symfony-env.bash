#!/usr/bin/env bash

set -ex

cp composer.json composer.json.origin
rm composer.lock || true
rm -Rf vendor || true
sed -i 's/\^5.4 || \^6.0 || \^7.0/\^5.4/g' composer.json
composer install