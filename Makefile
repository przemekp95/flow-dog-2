PHP ?= scripts/run_php.sh
COMPOSER ?= scripts/run_composer.sh
PHP_EXPECTED_VERSION ?= 8.4.20
PHP_EXPECTED_VERSION_ID ?= 80420

.PHONY: install install-hooks precommit prepush bootstrap-qa validate-composer assert-php-patch test stan cs-check cs-fix deptrac audit prettier-check prettier-write qa qa-ci serve docker-build docker-smoke docker-up docker-test public-smoke

install:
	scripts/run_composer.sh install

install-hooks:
	bash scripts/install_git_hooks.sh

precommit:
	bash scripts/run_pre_commit_checks.sh --all

prepush:
	bash scripts/run_pre_push_checks.sh

bootstrap-qa:
	bash scripts/bootstrap_local_qa.sh

validate-composer:
	scripts/run_composer.sh validate --strict

assert-php-patch:
	scripts/run_php.sh -r "if ((string) PHP_VERSION_ID !== '$(PHP_EXPECTED_VERSION_ID)') { fwrite(STDERR, 'Expected PHP $(PHP_EXPECTED_VERSION), got '.PHP_VERSION.PHP_EOL); exit(1);} echo 'Using PHP '.PHP_VERSION.PHP_EOL;"

test:
	scripts/run_php.sh bin/phpunit

stan:
	scripts/run_php.sh vendor/bin/phpstan analyse --memory-limit=1G

cs-check:
	scripts/run_php.sh vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

cs-fix:
	scripts/run_php.sh vendor/bin/php-cs-fixer fix --verbose

deptrac:
	scripts/run_php.sh vendor/bin/deptrac analyse --no-progress

audit:
	scripts/run_composer.sh audit

prettier-check:
	npm run format:check

prettier-write:
	npm run format:write

qa: test stan cs-check deptrac

qa-ci: bootstrap-qa assert-php-patch validate-composer test stan cs-check deptrac prettier-check audit docker-build docker-smoke

serve:
	scripts/run_php.sh -S 0.0.0.0:8080 -t public

docker-build:
	docker build --pull --target runtime -t flowdog-order-api:local .

docker-smoke:
	bash scripts/docker_smoke_test.sh \
		--image flowdog-order-api:local \
		--host-port 18080 \
		--app-env prod \
		--app-secret local-smoke-secret-not-for-production \
		--openapi-output /tmp/flowdog-openapi.json

docker-up:
	docker compose up --build --pull always

docker-test:
	DOCKER_TARGET=qa docker compose run --build --rm \
		-e APP_ENV=test \
		-e APP_SECRET=test-secret-not-for-production \
		app php bin/phpunit

public-smoke:
	if [ -z "$(BASE_URL)" ]; then echo "Usage: make public-smoke BASE_URL=https://example.com"; exit 1; fi
	bash scripts/public_smoke_test.sh --base-url "$(BASE_URL)"
