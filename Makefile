PHP_SERVICE := php
PHP_CONTAINER := s_php_1
PREFIX := s

install:
	@make -s build
	@make -s composer
	@make -s database
	@make -s fixtures
	@make -s jwt-init

build:
	@docker-compose -p $(PREFIX) up -d --build

start:
	@docker-compose -p $(PREFIX) up -d

stop:
	@docker-compose -p $(PREFIX) stop

down:
	@docker-compose -p $(PREFIX) down --volumes
	@make -s clean

composer:
	@docker-compose -p $(PREFIX) exec -T $(PHP_SERVICE) composer install

database:
	@docker-compose -p $(PREFIX) exec -T $(PHP_SERVICE) bin/console doctrine:schema:update --force -q

fixtures:
	@docker-compose -p $(PREFIX) exec -T $(PHP_SERVICE) bin/console doctrine:fixtures:load -qn

clean:
	@docker system prune --volumes --force

init-worker:
	@docker exec -u 0 -t $(PHP_CONTAINER) service beanstalkd start
	@docker exec -u 0 -t $(PHP_CONTAINER) /etc/init.d/supervisor start

reload-worker:
	@docker-compose -p $(PREFIX) exec -T $(PHP_SERVICE) bin/console cache:clear
	@docker exec -u 0 -t $(PHP_CONTAINER) supervisorctl restart all

php-bash:
	@docker exec -it -u 1000 $(PHP_CONTAINER) /bin/bash

php-bash-root:
	@docker exec -it -u 0 $(PHP_CONTAINER) /bin/bash

jwt-init:
	@docker exec -u 0 -t $(PHP_CONTAINER) /bin/bash ./docker/php/jwt-init.sh
