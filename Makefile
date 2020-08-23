SHELL := /bin/bash
PHP := $(shell which php)
COMPOSER := $(PHP) $(shell which composer)

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
coverage: vendor .build test-refresh-fixture
	$(PHP) vendor/bin/phpunit --config=test/phpunit.xml --coverage-text

.PHONY: cs
cs: cs-fixer-dry-run cs-phpstan cs-psalm cs-soft-require cs-composer-unused cs-composer-normalize

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
	$(COMPOSER) normalize --diff --dry-run composer.json

.PHONY: cs-fix-composer-normalize
cs-fix-composer-normalize: vendor
	$(COMPOSER) normalize --diff composer.json

.PHONY: csfix
csfix: vendor .build
	$(PHP) vendor/bin/php-cs-fixer fix --config=dev-ops/php_cs.php --diff --verbose

.PHONY: infection
infection: test-setup-fixture run-infection test-clean-fixture

.PHONY: run-infection
run-infection: vendor .build
	$(PHP) vendor/bin/infection --min-covered-msi=80 --min-msi=80 --configuration=dev-ops/infection.json

.PHONY: test
test: test-setup-fixture run-phpunit test-clean-fixture

.PHONY: run-phpunit
run-phpunit: vendor .build
	$(PHP) vendor/bin/phpunit --config=test/phpunit.xml

.PHONY: composer-update
composer-update:
	$(COMPOSER) update

vendor: composer-update
.PHONY: .build
.build:
	[[ -d .build ]] || mkdir .build

composer.lock: vendor

.PHONY: test-refresh-fixture
test-refresh-fixture: test-setup-fixture test-clean-fixture

.PHONY: test-setup-fixture
test-setup-fixture: vendor
	[[ ! -d test-composer-integration/vendor ]] || rm -rf test/Fixture/composer-integration/vendor
	[[ ! -d test-composer-integration/vendor ]] || rm -rf test-composer-integration/vendor
	[[ ! -f test-composer-integration/composer.lock ]] || rm test-composer-integration/composer.lock
	composer install -d test-composer-integration/

.PHONY: test-clean-fixture
test-clean-fixture:
	[[ ! -d test-composer-integration/vendor ]] || rm -rf test-composer-integration/vendor
