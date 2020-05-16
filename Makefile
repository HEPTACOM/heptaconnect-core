.PHONY: clean
clean:
	[[ ! -f composer.lock ]] || rm composer.lock
	[[ ! -d vendor ]] || rm -rf vendor
	[[ ! -d .build ]] || rm -rf .build

.PHONY: it
it: csfix cs test

.PHONY: coverage
coverage: vendor .build
	vendor/bin/phpunit --config=test/phpunit.xml --coverage-text

.PHONY: cs
cs: cs-fixer-dry-run cs-phpstan cs-psalm cs-soft-require

.PHONY: cs-fixer-dry-run
cs-fixer-dry-run: vendor .build
	vendor/bin/php-cs-fixer fix --dry-run --config=dev-ops/php_cs.php --diff --verbose

.PHONY: cs-phpstan
cs-phpstan: vendor .build
	vendor/bin/phpstan analyse -c dev-ops/phpstan.neon

.PHONY: cs-psalm
cs-psalm: vendor .build
	vendor/bin/psalm -c $(shell pwd)/dev-ops/psalm.xml

.PHONY: cs-soft-require
cs-soft-require: vendor .build
	vendor/bin/composer-require-checker check --config-file=dev-ops/composer-soft-requirements.json composer.json

.PHONY: csfix
csfix: vendor .build
	vendor/bin/php-cs-fixer fix --config=dev-ops/php_cs.php --diff --verbose

.PHONY: infection
infection: vendor .build
	vendor/bin/infection --min-covered-msi=80 --min-msi=80 --configuration=dev-ops/infection.json

.PHONY: test
test: vendor .build
	vendor/bin/phpunit --config=test/phpunit.xml

vendor: composer.json
	composer validate
	composer install

.PHONY: .build
.build:
	[[ -d .build ]] || mkdir .build

composer.lock: vendor
