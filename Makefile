build:
	docker compose -f ./.docker/docker-compose.yml build
serve:
	docker compose -f ./.docker/docker-compose.yml up -d
stop:
	docker compose -f ./.docker/docker-compose.yml stop
up:
	docker compose -f ./.docker/docker-compose.yml up
down:
	docker compose -f ./.docker/docker-compose.yml down
destroy:
	docker compose -f ./.docker/docker-compose.yml down -v
exec:
	docker compose -f ./.docker/docker-compose.yml run php-fpm bash
