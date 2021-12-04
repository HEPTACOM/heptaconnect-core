SHELL := /bin/bash
PHP := $(shell which php) $(PHP_EXTRA_ARGS)
COMPOSER := $(PHP) $(shell which composer) $(COMPOSER_EXTRA_ARGS)
PHPUNIT_EXTRA_ARGS := --config=test/phpunit.xml
PHPUNIT := $(PHP) vendor/bin/phpunit $(PHPUNIT_EXTRA_ARGS)
CURL := $(shell which curl)

.DEFAULT_GOAL := help
.PHONY: help
help: ## List useful make targets
	@echo 'Available make targets'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: all
all: clean it coverage ## Cleans up and runs typical tests and style analysis

.PHONY: clean
clean: ## Cleans up all ignored files and directories
	[[ ! -f composer.lock ]] || rm composer.lock
	[[ ! -d vendor ]] || rm -rf vendor
	[[ ! -d .build ]] || rm -rf .build

.PHONY: it
it: cs-fix cs test ## Fix code style and run unit tests

.PHONY: coverage
coverage: vendor .build test-refresh-fixture ## Run phpunit coverage tests
	$(PHPUNIT) --coverage-text

.PHONY: cs
cs: cs-phpmd cs-composer-unused cs-composer-normalize ## Run every code style check target

.PHONY: cs-phpmd
cs-phpmd: vendor .build ## Run php mess detector for static code analysis
	# TODO Re-add rulesets/unused.xml when phpmd fixes false-positive UnusedPrivateField
	$(PHP) vendor/bin/phpmd --ignore-violations-on-exit src ansi rulesets/codesize.xml,rulesets/naming.xml
	[[ -f .build/phpmd-junit.xslt ]] || $(CURL) https://phpmd.org/junit.xslt -o .build/phpmd-junit.xslt
	$(PHP) vendor/bin/phpmd src xml rulesets/codesize.xml,rulesets/naming.xml | xsltproc .build/phpmd-junit.xslt - > .build/php-md.junit.xml

.PHONY: cs-composer-unused
cs-composer-unused: vendor ## Run composer-unused to detect once-required packages that are not used anymore
	$(COMPOSER) unused --no-progress

.PHONY: cs-composer-normalize
cs-composer-normalize: vendor ## Run composer-normalize for composer.json style analysis
	$(COMPOSER) normalize --diff --dry-run --no-check-lock --no-update-lock composer.json

.PHONY: cs-fix ## Run all code style fixer that change files
cs-fix: cs-fix-composer-normalize

.PHONY: cs-fix-composer-normalize
cs-fix-composer-normalize: vendor ## Run composer-normalize for automatic composer.json style fixes
	$(COMPOSER) normalize --diff composer.json

.PHONY: test
test: test-setup-fixture run-phpunit test-clean-fixture ## Run phpunit for unit tests

.PHONY: run-phpunit
run-phpunit: vendor .build
	$(PHPUNIT) --log-junit=.build/.phpunit-coverage/phpunit.junit.xml

test/%Test.php: vendor
	$(PHPUNIT) "$@"

.PHONY: composer-update
composer-update:
	[[ -f vendor/autoload.php && -n "${CI}" ]] || $(COMPOSER) update

vendor: composer-update
.PHONY: .build
.build:
	[[ -d .build ]] || mkdir .build

composer.lock: vendor

.PHONY: test-refresh-fixture
test-refresh-fixture: test-setup-fixture test-clean-fixture

.PHONY: test-setup-fixture
test-setup-fixture: vendor
	[[ ! -d test/Fixture/composer-integration/vendor ]] || rm -rf test/Fixture/composer-integration/vendor
	[[ ! -d test-composer-integration/vendor ]] || rm -rf test-composer-integration/vendor
	[[ ! -f test-composer-integration/composer.lock ]] || rm test-composer-integration/composer.lock
	composer install -d test-composer-integration/

.PHONY: test-clean-fixture
test-clean-fixture:
	[[ ! -d test-composer-integration/vendor ]] || rm -rf test-composer-integration/vendor
