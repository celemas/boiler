#!/bin/sh
# Builds the benchmark image and runs the benchmark in a container of it.
# Arguments go to bench/run.php.
set -e

cd "$(dirname "$0")/.."

# The image has no Git history, so the commit of a saved run comes from here.
revision=$(git rev-parse --short HEAD 2>/dev/null || true)

if [ -n "$revision" ] && ! git diff --quiet HEAD 2>/dev/null; then
	revision="$revision-dirty"
fi

mkdir -p .bench
docker build --quiet --file bench/Dockerfile --tag celema-boiler-bench . > /dev/null
docker image prune --force --filter label=dev.celema.boiler.bench > /dev/null

# Runs as the calling user, so that the saved results belong to that user.
exec docker run --rm --init \
	--user "$(id -u):$(id -g)" \
	--volume "$PWD/.bench:/boiler/.bench" \
	--env BENCH_REVISION="${revision:-unknown}" \
	--env BENCH_TIME="$(date +%Y-%m-%d-%H%M%S)" \
	celema-boiler-bench "$@"
