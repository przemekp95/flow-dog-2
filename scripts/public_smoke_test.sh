#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
PHP_BIN="$("$ROOT_DIR/scripts/resolve_php_bin.sh")"
base_url=""
openapi_output="/tmp/flowdog-public-openapi.json"

log() {
    printf '[public-smoke] %s\n' "$*"
}

fail() {
    log "$*" >&2
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --base-url)
            base_url="$2"
            shift 2
            ;;
        --openapi-output)
            openapi_output="$2"
            shift 2
            ;;
        *)
            fail "Unknown argument: $1"
            ;;
    esac
done

[[ -n "$base_url" ]] || fail '--base-url is required.'
base_url="${base_url%/}"

case "$base_url" in
    https://*)
        ;;
    http://*)
        fail 'Use an https:// base URL for public smoke tests. Public http:// usually redirects and can break POST semantics.'
        ;;
    *)
        fail '--base-url must start with https://'
        ;;
esac

mkdir -p "$(dirname "$openapi_output")"

log "Fetching OpenAPI JSON from ${base_url}/api/doc.json"
"$PHP_BIN" -r '
    $baseUrl = $argv[1];
    $outputFile = $argv[2];

    $body = @file_get_contents($baseUrl . "/api/doc.json");
    if ($body === false) {
        fwrite(STDERR, "Failed to fetch OpenAPI JSON\n");
        exit(1);
    }

    $payload = json_decode($body, true);
    if (!is_array($payload) || !isset($payload["openapi"])) {
        fwrite(STDERR, "OpenAPI payload is invalid\n");
        exit(1);
    }

    $servers = $payload["servers"] ?? null;
    if (!is_array($servers) || !isset($servers[0]["url"]) || $servers[0]["url"] !== $baseUrl) {
        fwrite(STDERR, "OpenAPI servers[0].url does not match base URL\n");
        exit(1);
    }

    if (file_put_contents($outputFile, $body) === false) {
        fwrite(STDERR, "Failed to write OpenAPI output file\n");
        exit(1);
    }
' "$base_url" "$openapi_output"

log "Posting sample order to ${base_url}/orders"
"$PHP_BIN" -r '
    $baseUrl = $argv[1];

    $payload = json_encode([
        "customerId" => 123,
        "items" => [
            [
                "productId" => 10,
                "quantity" => 2,
            ],
        ],
        "couponCode" => "PROMO10",
    ], JSON_THROW_ON_ERROR);

    $context = stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\n",
            "content" => $payload,
            "ignore_errors" => true,
            "timeout" => 20,
        ],
    ]);

    $body = @file_get_contents($baseUrl . "/orders", false, $context);
    $headers = $http_response_header ?? [];
    $statusLine = $headers[0] ?? "";

    if (!preg_match("/\\s(\\d{3})\\s/", $statusLine, $matches) || (int) $matches[1] !== 201) {
        fwrite(STDERR, "Expected HTTP 201, got: " . $statusLine . "\n");
        if (is_string($body)) {
            fwrite(STDERR, $body . "\n");
        }
        exit(1);
    }

    $response = json_decode((string) $body, true);
    if (!is_array($response)) {
        fwrite(STDERR, "Response is not valid JSON\n");
        exit(1);
    }

    if (($response["total"] ?? null) !== 216) {
        fwrite(STDERR, "Unexpected total in sample order response\n");
        exit(1);
    }

    if (($response["couponCode"] ?? null) !== "PROMO10") {
        fwrite(STDERR, "Unexpected couponCode in sample order response\n");
        exit(1);
    }
' "$base_url"

log "Public smoke test passed."
