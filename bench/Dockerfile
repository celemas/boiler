# Build from the repository root: docker build --file bench/Dockerfile .
ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-fpm

# Lets the benchmark command find and remove the images that a rebuild replaced.
LABEL dev.celema.boiler.bench=1

ARG FRANKENPHP_VERSION=1.13.0

RUN apt-get update \
	&& apt-get install --yes --no-install-recommends unzip \
	&& rm -rf /var/lib/apt/lists/* \
	&& curl --fail --silent --show-error --location \
		--output /usr/local/bin/frankenphp \
		"https://github.com/php/frankenphp/releases/download/v${FRANKENPHP_VERSION}/frankenphp-linux-$(uname -m)-gnu" \
	&& chmod +x /usr/local/bin/frankenphp

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /boiler

# Installs the engines against the manifests alone, so that a change to the
# sources does not install them again.
COPY composer.json ./
COPY bench/composer.json bench/
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --working-dir=bench --no-interaction --no-progress --prefer-dist

# Copied, not mounted: a mounted folder slows down file access, and the
# engines differ in how much of it a request needs.
COPY src src
COPY bench bench
# Writable for any user: the benchmark command runs the container as its caller.
RUN mkdir -p bench/cache && chmod 1777 bench/cache

ENV BENCH_ENVIRONMENT=container

# PHP-FPM refuses to serve as root.
USER www-data

ENTRYPOINT ["php", "-d", "opcache.enable_cli=1", "-d", "opcache.file_update_protection=0", "bench/run.php"]
