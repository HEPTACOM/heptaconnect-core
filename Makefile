.PHONY: coverage cs csfix infection integration it test statcs

clear:
	[[ ! -f composer.lock ]] || rm composer.lock
	[[ ! -d vendor ]] || rm -rf vendor
	[[ ! -d vendor ]] || rm -rf .build
	[[ -d .build ]] || mkdir .build
	[[ -f .build/.gitkeep ]] || touch .build/.gitkeep

it: csfix statcs test

coverage: vendor
	vendor/bin/phpunit --config=test/phpunit.xml --coverage-text

cs: vendor
	vendor/bin/php-cs-fixer fix --dry-run --config=.php_cs.php --diff --verbose

csfix: vendor
	vendor/bin/php-cs-fixer fix --config=.php_cs.php --diff --verbose

statcs: vendor
	vendor/bin/psalm -c .psalm.xml

infection: vendor
	vendor/bin/infection --min-covered-msi=80 --min-msi=80 --configuration=.infection.json

test: vendor
	vendor/bin/phpunit --config=test/phpunit.xml

vendor: composer.json
	composer validate
	composer install

composer.lock: vendor
