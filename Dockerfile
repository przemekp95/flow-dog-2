FROM php:8.4.20-cli-alpine3.22 AS php-base

# Bump this when Alpine ships security fixes behind the same PHP base tag to force a fresh apk upgrade layer.
ARG ALPINE_SECURITY_REFRESH=2026-04-15

RUN set -eux; \
    echo "alpine-security-refresh=${ALPINE_SECURITY_REFRESH}"; \
    apk upgrade --no-cache

FROM composer/composer:2-bin AS composer-bin

FROM php-base AS build-base

WORKDIR /app

COPY --from=composer-bin /composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer/cache \
    COMPOSER_MAX_PARALLEL_HTTP=4 \
    COMPOSER_PROCESS_TIMEOUT=900

RUN apk add --no-cache git unzip

COPY composer.json composer.lock symfony.lock ./

FROM build-base AS deps-dev

ENV APP_ENV=dev

RUN set -eux; \
    attempt=1; \
    until composer install --no-interaction --prefer-dist --no-progress --no-scripts; do \
        if [ "$attempt" -ge 3 ]; then \
            exit 1; \
        fi; \
        sleep $((attempt * 10)); \
        attempt=$((attempt + 1)); \
    done

FROM deps-dev AS build-dev

COPY . .

RUN set -eux; \
    composer dump-autoload --optimize; \
    build_secret="$(cat /proc/sys/kernel/random/uuid)"; \
    APP_ENV=dev APP_SECRET="$build_secret" php bin/console cache:clear; \
    APP_ENV=dev APP_SECRET="$build_secret" php bin/console assets:install public

FROM php-base AS qa

WORKDIR /app

ENV APP_ENV=dev

RUN addgroup -S app && adduser -S -G app -h /app app && mkdir -p /app/templates

COPY --from=build-dev --chown=app:app /app /app

USER app

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 CMD ["php", "-r", "exit(@file_get_contents('http://127.0.0.1:8080/api/doc.json') === false ? 1 : 0);"]

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

FROM build-base AS deps-prod

ENV APP_ENV=prod

RUN set -eux; \
    attempt=1; \
    until composer install --no-dev --classmap-authoritative --no-interaction --prefer-dist --no-progress --no-scripts; do \
        if [ "$attempt" -ge 3 ]; then \
            exit 1; \
        fi; \
        sleep $((attempt * 10)); \
        attempt=$((attempt + 1)); \
    done

FROM deps-prod AS build-prod

ENV DEFAULT_URI=http://localhost:8080

COPY . .

RUN set -eux; \
    composer dump-autoload --no-dev --classmap-authoritative --optimize; \
    build_secret="$(cat /proc/sys/kernel/random/uuid)"; \
    APP_ENV=prod APP_DEBUG=0 APP_SECRET="$build_secret" php bin/console cache:clear; \
    APP_ENV=prod APP_DEBUG=0 APP_SECRET="$build_secret" php bin/console assets:install public; \
    rm -rf \
        public/bundles/nelmioapidoc/redocly \
        public/bundles/nelmioapidoc/scalar \
        public/bundles/nelmioapidoc/stoplight \
        vendor/bin \
        vendor/nelmio/api-doc-bundle/public; \
    rm -f \
        public/bundles/nelmioapidoc/init-redocly-ui.js \
        public/bundles/nelmioapidoc/swagger-ui/swagger-ui-bundle.js.map \
        public/bundles/nelmioapidoc/swagger-ui/swagger-ui-standalone-preset.js.map \
        public/bundles/nelmioapidoc/swagger-ui/swagger-ui.css.map

FROM php-base AS runtime

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    DEFAULT_URI=http://localhost:8080

RUN addgroup -S app && adduser -S -G app -h /app app && mkdir -p /app/templates

COPY --from=build-prod --chown=app:app /app/bin /app/bin
COPY --from=build-prod --chown=app:app /app/composer.json /app/composer.json
COPY --from=build-prod --chown=app:app /app/config /app/config
COPY --from=build-prod --chown=app:app /app/public /app/public
COPY --from=build-prod --chown=app:app /app/src /app/src
COPY --from=build-prod --chown=app:app /app/var/cache /app/var/cache
COPY --from=build-prod --chown=app:app /app/vendor /app/vendor

USER app

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 CMD ["php", "-r", "exit(@file_get_contents('http://127.0.0.1:8080/api/doc.json') === false ? 1 : 0);"]

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
