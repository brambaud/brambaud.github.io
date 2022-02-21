.DEFAULT_GOAL := build

build:
	php tools/piggy-static-generate.phar --config $(PWD)/.piggy-static.php
	mv build/rss.html build/rss.xml
	mv build/sitemap.html build/sitemap.xml
	echo "google-site-verification: google66cdc87c4c5953c6.html" > build/google66cdc87c4c5953c6.html

.PHONY: clean
clean:
	rm -rf build/

.PHONY: serve
serve: build
	php -S localhost:8080 -t build/

.PHONY: build-force
build-force: clean build
