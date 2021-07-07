.PHONY:help test clean
.DEFAULT_GOAL=help

help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

composer.phar:
	@curl -sS https://getcomposer.org/installer | php -- --filename=composer.phar
	@chmod +x composer.phar

vendor: composer.json
	@./composer.phar update --optimize-autoloader --no-suggest

tests: composer.phar vendor phpunit.xml ## Launch test
	@php vendor/bin/phpunit

clean: ## Remove files needed for tests
	@rm -rf report testbench vendor .phpunit.result.cache composer.lock composer.phar