SHELL := /bin/bash
PHP := $(shell which php) $(PHP_EXTRA_ARGS)
COMPOSER := $(PHP) $(shell which composer)
JQ := $(shell which jq)
JSON_FILES := $(shell find . -name '*.json' -not -path './vendor/*')

.PHONY: all
all: clean it coverage infection

.PHONY: clean
clean:
	[[ ! -f composer.lock ]] || rm composer.lock
	[[ ! -d vendor ]] || rm -rf vendor
	[[ ! -d .build ]] || rm -rf .build

.PHONY: it
it: cs-fix-composer-normalize csfix cs test

.PHONY: coverage
coverage: vendor .build
	$(PHP) vendor/bin/phpunit --config=test/phpunit.xml --coverage-text

.PHONY: cs
cs: cs-fixer-dry-run cs-phpstan cs-psalm cs-soft-require cs-composer-unused cs-composer-normalize cs-json

.PHONY: cs-fixer-dry-run
cs-fixer-dry-run: vendor .build
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --config=dev-ops/php_cs.php --diff --verbose

.PHONY: cs-phpstan
cs-phpstan: vendor .build
	$(PHP) vendor/bin/phpstan analyse -c dev-ops/phpstan.neon

.PHONY: cs-psalm
cs-psalm: vendor .build
	$(PHP) vendor/bin/psalm -c $(shell pwd)/dev-ops/psalm.xml

.PHONY: cs-composer-unused
cs-composer-unused: vendor
	$(COMPOSER) unused --no-progress

.PHONY: cs-soft-require
cs-soft-require: vendor .build
	$(PHP) vendor/bin/composer-require-checker check --config-file=dev-ops/composer-soft-requirements.json composer.json

.PHONY: cs-composer-normalize
cs-composer-normalize: vendor
	$(COMPOSER) normalize --diff --dry-run --no-check-lock --no-update-lock composer.json

.PHONY: cs-json
cs-json: $(JSON_FILES)

.PHONY: $(JSON_FILES)
$(JSON_FILES):
	$(JQ) . "$@"

.PHONY: cs-fix-composer-normalize
cs-fix-composer-normalize: vendor
	$(COMPOSER) normalize --diff composer.json

.PHONY: csfix
csfix: vendor .build
	$(PHP) vendor/bin/php-cs-fixer fix --config=dev-ops/php_cs.php --diff --verbose

.PHONY: infection
infection: vendor .build
	# Can be simplified when infection/infection#1283 is resolved
	[[ -d .build/phpunit-logs ]] || mkdir -p .build/.phpunit-coverage
	$(PHP) vendor/bin/phpunit --config=test/phpunit.xml --coverage-xml=.build/.phpunit-coverage/xml --log-junit=.build/.phpunit-coverage/infection.junit.xml
	$(PHP) vendor/bin/infection --min-covered-msi=80 --min-msi=80 --configuration=dev-ops/infection.json --coverage=../.build/.phpunit-coverage --show-mutations --no-interaction

.PHONY: test
test: vendor .build
	$(PHP) vendor/bin/phpunit --config=test/phpunit.xml

.PHONY: composer-update
composer-update:
	$(COMPOSER) update

vendor: composer-update

.PHONY: .build
.build:
	[[ -d .build ]] || mkdir .build

composer.lock: vendor
