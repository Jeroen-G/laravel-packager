.PHONY: all

info: do-show-commands

do-show-commands:
	@echo "\n=== Make commands ===\n"
	@awk 'BEGIN {FS = ":.*?[#][#][ ]?"} \
	 /^[a-zA-Z_-]*:?.*?[#][#][ ]?/ {printf "\033[33m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST) |\
	sed 's/[#]{2,}[ ]*//g'

install: ## Install project dependencies
	@echo "\n=== Installing composer dependencies ===\n"
	COMPOSER_MEMORY_LIMIT=-1 composer install

cs:
	make codestyle

codestyle: ## Check the codestyle
	./vendor/bin/ecs check --config=easy-coding-standard.php .

codestyle-fix: ## Fix your mess
	./vendor/bin/ecs check --fix --config=easy-coding-standard.php .

test: ## Run PHPUnit with coverage
	@echo "\n=== Running unit tests ===\n"
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html ./report
	@echo "\n=== Click the link below to see the test coverage report ===\n"
	@echo "report/index.html"

infection: ## Run InfectionPHP with coverage
	@echo "\n=== Running unit tests ===\n"
	XDEBUG_MODE=coverage vendor/bin/infection --threads=4 --min-covered-msi=100 --min-msi=100
	@echo "\n=== Click the link below to see the mutation coverage report ===\n"
	@echo "report/infection.html"
