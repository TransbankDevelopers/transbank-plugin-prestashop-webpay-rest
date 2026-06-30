#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="$PROJECT_ROOT/webpay"

RELEASE_TAG="${RELEASE_TAG:-}"
PACKAGE_OUTPUT="${PACKAGE_OUTPUT:-}"
PLUGIN_VERSION=""

require_command() {
    local cmd="$1"
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "ERROR: missing required command: $cmd" 1>&2
        exit 1
    fi
}

run_step() {
    local name="$1"
    shift
    echo "Running: $name"
    "$@"
}

on_error() {
    local exit_code=$?
    local line_no="${1:-unknown}"
    echo "ERROR: packaging failed at line ${line_no} with exit code ${exit_code}" 1>&2
    exit "$exit_code"
}

cleanup() {
    return 0
}

resolve_package_version() {
    local version="${RELEASE_TAG#v}"

    if [[ -z "$version" ]]; then
        version="1.0.0"
    fi

    PLUGIN_VERSION="$version"
}

escape_sed_replacement() {
    printf '%s' "$1" | sed 's/[\\/&]/\\&/g'
}

replace_version_strings() {
    local version="$1"
    local escaped_version

    escaped_version="$(escape_sed_replacement "$version")"

    sed -i.bkp "s/\$this->version = '1.0.0'/\$this->version = '$escaped_version'/g" "$SOURCE_DIR/webpay.php"
    sed -i.bkp "s/\[1.0.0\]/\[$escaped_version\]/g" "$SOURCE_DIR/config.xml"
    sed -i.bkp "s/\[1.0.0\]/\[$escaped_version\]/g" "$SOURCE_DIR/config_es.xml"
    rm -f "$SOURCE_DIR"/*.bkp "$SOURCE_DIR"/*.bak
}

install_dependencies() {
    (
        cd "$SOURCE_DIR"
        run_step "Composer install" composer install --no-dev --no-interaction --prefer-dist
    )

    if [[ ! -d "$SOURCE_DIR/vendor" ]]; then
        echo "ERROR: vendor directory not created" 1>&2
        exit 1
    fi

    if [[ ! -f "$SOURCE_DIR/vendor/autoload.php" ]]; then
        echo "ERROR: vendor/autoload.php not found" 1>&2
        exit 1
    fi
}

create_zip() {
    local output_name="$1"
    local output_path="$PROJECT_ROOT/$output_name"
    local has_vendor=0

    rm -f "$output_path"
    (
        cd "$PROJECT_ROOT"
        zip -rq "$output_path" webpay
    )

    if command -v unzip >/dev/null 2>&1; then
        while IFS= read -r entry; do
            case "$entry" in
                webpay/vendor/*|./webpay/vendor/*)
                    has_vendor=1
                    ;;
                *)
                    ;;
            esac
        done < <(unzip -Z1 "$output_path")

        if [[ "$has_vendor" != "1" ]]; then
            echo "ERROR: vendor directory not found inside ZIP" 1>&2
            exit 1
        fi
    fi
}

package_plugin() {
    require_command composer
    require_command php
    require_command zip
    require_command sed

    resolve_package_version

    echo "Packaging version ${PLUGIN_VERSION}"
    replace_version_strings "$PLUGIN_VERSION"
    install_dependencies

    if [[ -z "$PACKAGE_OUTPUT" ]]; then
        if [[ -n "$RELEASE_TAG" ]]; then
            local safe_release_tag="${RELEASE_TAG//[^A-Za-z0-9._-]/_}"
            PACKAGE_OUTPUT="plugin-prestashop-webpay-rest-${safe_release_tag}.zip"
        else
            PACKAGE_OUTPUT="plugin-prestashop-webpay-rest.zip"
        fi
    fi

    run_step "Create ZIP" create_zip "$PACKAGE_OUTPUT"

    echo
    echo "Package created successfully:"
    echo "- Version: $PLUGIN_VERSION"
    echo "- File name: $PACKAGE_OUTPUT"
}

trap 'on_error $LINENO' ERR
trap cleanup EXIT

package_plugin
