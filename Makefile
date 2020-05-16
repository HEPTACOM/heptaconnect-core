.PHONY: coverage cs csfix infection integration it test

clear:
	[[ ! -f composer.lock ]] || rm composer.lock
	[[ ! -d vendor ]] || rm -rf vendor
	[[ ! -d .build ]] || rm -rf .build

it: csfix cs test

coverage: vendor .build
	vendor/bin/phpunit --config=test/phpunit.xml --coverage-text

cs: vendor .build
	vendor/bin/php-cs-fixer fix --dry-run --config=dev-ops/php_cs.php --diff --verbose
	vendor/bin/psalm -c dev-ops/psalm.xml

csfix: vendor .build
	vendor/bin/php-cs-fixer fix --config=dev-ops/php_cs.php --diff --verbose

infection: vendor .build
	vendor/bin/infection --min-covered-msi=80 --min-msi=80 --configuration=dev-ops/infection.json

test: vendor .build
	vendor/bin/phpunit --config=test/phpunit.xml

vendor: composer.json
	composer validate
	composer install

.build:
	[[ -d .build ]] || mkdir .build

composer.lock: vendor
