#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

PHP_EXPECTED_VERSION="${PHP_EXPECTED_VERSION:-8.4.20}"
PHP_EXPECTED_VERSION_ID="${PHP_EXPECTED_VERSION_ID:-80420}"

log() {
    printf '[bootstrap] %s\n' "$*"
}

fail() {
    printf '[bootstrap] %s\n' "$*" >&2
    exit 1
}

have_command() {
    command -v "$1" >/dev/null 2>&1
}

run_privileged() {
    if have_command sudo; then
        sudo "$@"
        return
    fi

    if [[ "$(id -u)" -eq 0 ]]; then
        "$@"
        return
    fi

    fail "Missing privileges to run: $*"
}

detect_package_manager() {
    local manager

    for manager in apt-get dnf yum pacman apk brew zypper; do
        if have_command "$manager"; then
            printf '%s\n' "$manager"
            return
        fi
    done

    return 1
}

install_with_apt_get() {
    export DEBIAN_FRONTEND=noninteractive
    run_privileged apt-get update
    run_privileged apt-get install -y \
        ca-certificates \
        composer \
        curl \
        docker.io \
        make \
        nodejs \
        npm \
        php-cli \
        unzip
}

install_with_dnf() {
    run_privileged dnf install -y \
        composer \
        curl \
        docker \
        make \
        nodejs \
        npm \
        php-cli \
        unzip
}

install_with_yum() {
    run_privileged yum install -y \
        composer \
        curl \
        docker \
        make \
        nodejs \
        npm \
        php-cli \
        unzip
}

install_with_pacman() {
    run_privileged pacman -Sy --noconfirm \
        composer \
        curl \
        docker \
        make \
        nodejs \
        npm \
        php \
        unzip
}

install_with_apk() {
    run_privileged apk add --no-cache \
        bash \
        composer \
        curl \
        docker-cli \
        make \
        nodejs \
        npm \
        php84 \
        php84-cli \
        php84-openssl \
        php84-phar \
        unzip
}

install_with_brew() {
    brew install composer node php

    if ! have_command docker; then
        brew install --cask docker || true
    fi
}

install_with_zypper() {
    run_privileged zypper --non-interactive install \
        composer \
        curl \
        docker \
        make \
        nodejs \
        npm \
        php8 \
        unzip
}

install_missing_tooling() {
    local manager
    manager="$(detect_package_manager)" || fail 'Could not detect a supported package manager for automatic setup.'

    log "Installing missing local tooling via ${manager}."

    case "$manager" in
        apt-get)
            install_with_apt_get
            ;;
        dnf)
            install_with_dnf
            ;;
        yum)
            install_with_yum
            ;;
        pacman)
            install_with_pacman
            ;;
        apk)
            install_with_apk
            ;;
        brew)
            install_with_brew
            ;;
        zypper)
            install_with_zypper
            ;;
        *)
            fail "Unsupported package manager: ${manager}"
            ;;
    esac
}

ensure_tooling() {
    local missing=()
    local tool

    for tool in php composer npm docker curl; do
        if ! have_command "$tool"; then
            missing+=("$tool")
        fi
    done

    if [[ ${#missing[@]} -eq 0 ]]; then
        log 'Required tooling is already installed.'
        return
    fi

    log "Missing tooling: ${missing[*]}"
    install_missing_tooling

    for tool in php composer npm docker curl; do
        have_command "$tool" || fail "Automatic setup finished, but '${tool}' is still unavailable."
    done
}

ensure_docker_available() {
    if docker info >/dev/null 2>&1; then
        log 'Docker daemon is available.'
        return
    fi

    if have_command systemctl; then
        log 'Docker command is present, trying to start the daemon via systemctl.'
        run_privileged systemctl start docker || true
    elif have_command service; then
        log 'Docker command is present, trying to start the daemon via service.'
        run_privileged service docker start || true
    fi

    docker info >/dev/null 2>&1 || fail 'Docker is installed, but the daemon is not available. Start Docker and rerun make qa-ci.'
}

ensure_php_patch_hint() {
    local current_version_id

    current_version_id="$(php -r 'echo PHP_VERSION_ID;')"
    if [[ "${current_version_id}" != "${PHP_EXPECTED_VERSION_ID}" ]]; then
        log "PHP is installed, but expected ${PHP_EXPECTED_VERSION}. The dedicated patch check in qa-ci may still fail."
    fi
}

ensure_php_dependencies() {
    local vendor_autoload="${PROJECT_DIR}/vendor/autoload.php"

    if [[ -f "${vendor_autoload}" ]]; then
        log 'PHP dependencies are already installed.'
        return
    fi

    log 'Installing PHP dependencies with composer install.'
    (
        cd "${PROJECT_DIR}"
        composer install --no-interaction --prefer-dist --no-progress
    )
}

ensure_node_dependencies() {
    local node_modules_dir="${PROJECT_DIR}/node_modules"

    if [[ -d "${node_modules_dir}" ]]; then
        log 'Node dependencies are already installed.'
        return
    fi

    log 'Installing Node dependencies with npm ci.'
    (
        cd "${PROJECT_DIR}"
        npm ci
    )
}

main() {
    ensure_tooling
    ensure_docker_available
    ensure_php_patch_hint
    ensure_php_dependencies
    ensure_node_dependencies
}

main "$@"
