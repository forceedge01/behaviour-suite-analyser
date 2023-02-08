install:
	docker-compose run php7.1-test sh -c "composer install"
	docker-compose run php7.1-test sh -c "cd testproject && composer install"
	docker-compose run php7.1-test sh -c "cd vendor/forceedge01/bdd-analyser-rules && composer install"

update:
	docker-compose run php7.1-test sh -c "composer update"

.PHONY: tests
tests:
	docker-compose run php7.1-test sh -c "./vendor/bin/phpunit tests"

tests-deps:
	docker-compose run php7.1-test sh -c "cd ./vendor/forceedge01/bdd-analyser-rules && ./vendor/bin/phpunit tests"
