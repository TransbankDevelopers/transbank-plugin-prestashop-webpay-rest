#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="$PROJECT_ROOT/webpay"
WORK_DIR="$PROJECT_ROOT/build/package-webpay"

RELEASE_TAG="${RELEASE_TAG:-}"
PACKAGE_OUTPUT="${PACKAGE_OUTPUT:-}"
KEEP_BUILD_ARTIFACTS="${KEEP_BUILD_ARTIFACTS:-0}"
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
    if [[ "$KEEP_BUILD_ARTIFACTS" == "1" ]]; then
        echo "Keeping build directory for debugging: $PROJECT_ROOT/build"
        return 0
    fi

    rm -rf "$PROJECT_ROOT/build"
}

prepare_work_dir() {
    rm -rf "$WORK_DIR"
    mkdir -p "$WORK_DIR"

    if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete "$SOURCE_DIR/" "$WORK_DIR/"
    else
        cp -a "$SOURCE_DIR/." "$WORK_DIR/"
    fi
}

resolve_package_version() {
    local version="${RELEASE_TAG#v}"

    if [[ -z "$version" ]]; then
        version="${GITHUB_REF_NAME:-}"
        version="${version#v}"
    fi

    if [[ -z "$version" ]]; then
        if command -v git >/dev/null 2>&1 && [[ -d "$PROJECT_ROOT/.git" ]]; then
            version="$(git -C "$PROJECT_ROOT" describe --tags --always --dirty 2>/dev/null || true)"
        fi
    fi

    if [[ -z "$version" ]]; then
        version="local"
    fi

    PLUGIN_VERSION="$version"
}

replace_version_strings() {
    local version="$1"

    sed -i.bkp "s/\$this->version = '1.0.0'/\$this->version = '$version'/g" "webpay.php"
    sed -i.bkp "s/\[1.0.0\]/\[$version\]/g" "config.xml"
    sed -i.bkp "s/\[1.0.0\]/\[$version\]/g" "config_es.xml"
    rm -f ./*.bkp ./*.bak
}

install_dependencies() {
    run_step "Composer install" composer install --no-dev --no-interaction --prefer-dist

    if [[ ! -d vendor ]]; then
        echo "ERROR: vendor directory not created" 1>&2
        exit 1
    fi

    if [[ ! -f vendor/autoload.php ]]; then
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
        cd "$WORK_DIR"
        zip -rq "$output_path" .
    )

    if command -v unzip >/dev/null 2>&1; then
        while IFS= read -r entry; do
            if [[ "$entry" == vendor/* ]]; then
                has_vendor=1
                break
            fi
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

    prepare_work_dir
    (
        cd "$WORK_DIR"

        echo "Packaging version ${PLUGIN_VERSION}"
        replace_version_strings "$PLUGIN_VERSION"

        install_dependencies
    )

    if [[ -z "$PACKAGE_OUTPUT" ]]; then
        if [[ -n "$RELEASE_TAG" ]]; then
            PACKAGE_OUTPUT="plugin-prestashop-webpay-rest-${RELEASE_TAG}.zip"
        else
            PACKAGE_OUTPUT="plugin-prestashop-webpay-rest.zip"
        fi
    fi

    run_step "Create ZIP" create_zip "$PACKAGE_OUTPUT"

    echo
    echo "Package created successfully:"
    echo "- Version: $PLUGIN_VERSION"
    echo "- File name: $PACKAGE_OUTPUT"
    if [[ "$KEEP_BUILD_ARTIFACTS" == "1" ]]; then
        echo "- Build dir kept for debugging: $WORK_DIR"
    fi
}

trap 'on_error $LINENO' ERR
trap cleanup EXIT

package_plugin
