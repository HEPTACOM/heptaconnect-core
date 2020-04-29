.PHONY: coverage cs infection integration it test statcs

it: cs statcs test

coverage: vendor
	vendor/bin/phpunit --config=test/phpunit.xml --coverage-text

cs: vendor
	vendor/bin/php-cs-fixer fix --config=.php_cs.php --diff --verbose

statcs: vendor
	vendor/bin/psalm -c .psalm.xml

infection: vendor
	vendor/bin/infection --min-covered-msi=80 --min-msi=80 --configuration=.infection.json

test: vendor
	vendor/bin/phpunit --config=test/phpunit.xml

vendor: composer.json
	composer self-update
	composer validate
	composer install

composer.lock: vendor
