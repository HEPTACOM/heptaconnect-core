.PHONY: coverage cs csfix infection integration it test statcs

clear:
	[[ ! -f composer.lock ]] || rm composer.lock
	[[ ! -d vendor ]] || rm -rf vendor
	[[ ! -d .build ]] || rm -rf .build

it: csfix statcs test

coverage: vendor .build
	vendor/bin/phpunit --config=test/phpunit.xml --coverage-text

cs: vendor .build
	vendor/bin/php-cs-fixer fix --dry-run --config=dev-ops/php_cs.php --diff --verbose

csfix: vendor .build
	vendor/bin/php-cs-fixer fix --config=dev-ops/php_cs.php --diff --verbose

statcs: vendor .build
	vendor/bin/psalm -c dev-ops/psalm.xml

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
