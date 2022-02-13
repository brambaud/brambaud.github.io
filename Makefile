.DEFAULT_GOAL := build

build:
	php tools/piggy-static-generate.phar --config $(PWD)/.piggy-static.php
	mv build/rss.html build/rss.xml

.PHONY: clean
clean:
	rm -rf build/

.PHONY: serve
serve: build
	php -S localhost:8080 -t build/

.PHONY: build-force
build-force: clean build
