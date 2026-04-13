#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
image_ref="flowdog-order-api:local"
host_port="18080"
app_env="dev"
app_secret="local-smoke-secret-not-for-production"
openapi_output="/tmp/flowdog-openapi.json"

log() {
    printf '[docker-smoke] %s\n' "$*"
}

fail() {
    log "$*" >&2
    exit 1
}

resolve_docker_bin() {
    if command -v docker >/dev/null 2>&1; then
        command -v docker
        return 0
    fi

    local snap_docker
    snap_docker="$(find /snap/docker-core24 -path '*/bin/docker' -type f 2>/dev/null | sort | tail -n 1 || true)"
    if [[ -n "$snap_docker" && -x "$snap_docker" ]]; then
        printf '%s\n' "$snap_docker"
        return 0
    fi

    return 1
}

resolve_php_bin() {
    if command -v php >/dev/null 2>&1; then
        command -v php
        return 0
    fi

    if [[ -x "$ROOT_DIR/.tools/php84-common/php" ]]; then
        printf '%s\n' "$ROOT_DIR/.tools/php84-common/php"
        return 0
    fi

    return 1
}

fetch_openapi_json() {
    local url="$1"
    local output_file="$2"

    if command -v curl >/dev/null 2>&1; then
        curl --fail --silent "$url" > "$output_file"
        return 0
    fi

    local php_bin
    php_bin="$(resolve_php_bin)" || fail 'Neither curl nor PHP was found. Install curl, put php in PATH, or provide .tools/php84-common/php.'

    "$php_bin" -r '
        $url = $argv[1];
        $outputFile = $argv[2];
        $body = @file_get_contents($url);
        if ($body === false) {
            fwrite(STDERR, "HTTP fetch failed for {$url}\n");
            exit(1);
        }

        if (file_put_contents($outputFile, $body) === false) {
            fwrite(STDERR, "Failed to write {$outputFile}\n");
            exit(1);
        }
    ' "$url" "$output_file"
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --image)
            image_ref="$2"
            shift 2
            ;;
        --host-port)
            host_port="$2"
            shift 2
            ;;
        --app-env)
            app_env="$2"
            shift 2
            ;;
        --app-secret)
            app_secret="$2"
            shift 2
            ;;
        --openapi-output)
            openapi_output="$2"
            shift 2
            ;;
        *)
            echo "Unknown argument: $1" >&2
            exit 1
            ;;
    esac
done

DOCKER_BIN="$(resolve_docker_bin)" || fail 'Docker not found. Install docker or expose the snap docker binary in PATH.'

container_id="$("$DOCKER_BIN" run -d \
  -p "${host_port}:8080" \
  --health-interval=2s \
  --health-timeout=3s \
  --health-start-period=1s \
  --health-retries=15 \
  -e APP_ENV="${app_env}" \
  -e APP_SECRET="${app_secret}" \
  "${image_ref}")"

cleanup() {
    status=$?
    if [[ $status -ne 0 ]]; then
        "$DOCKER_BIN" logs "$container_id"
    fi
    "$DOCKER_BIN" rm -f "$container_id" >/dev/null 2>&1 || true
    exit "$status"
}

trap cleanup EXIT

for attempt in $(seq 1 30); do
    health_status="$("$DOCKER_BIN" inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "$container_id")"
    if [[ "$health_status" == "healthy" ]]; then
        break
    fi
    if [[ "$health_status" == "unhealthy" ]]; then
        echo "Container became unhealthy before smoke test completed." >&2
        exit 1
    fi
    sleep 2
done

fetch_openapi_json "http://127.0.0.1:${host_port}/api/doc.json" "${openapi_output}"
grep -q '"openapi"' "${openapi_output}"
