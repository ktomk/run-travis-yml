PHP_COMMAND ?= php
COMPOSER_COMMAND ?= lib/bin/composer
COMPOSER_VERSION ?= 2.0.12

export COMPOSER_CMD

.PHONY : all
all : install test

.PHONY : install
install : composer.lock

.PHONY : test
test :
	test/shell.sh

.PHONY : test-yaml
test-yaml : lib/bin/php
	env PATH="$(abspath lib/bin):$(PATH)" test/shell.sh

lib/bin/php : lib/phpbin.php
	$(PHP_COMMAND) $< '$(shell command -v '$(PHP_COMMAND)' 2> /dev/null)' $@ -n \
	  -d extension=phar.so -d extension=json.so -d extension=iconv.so -d extension=zip.so -d extension=curl.so \
	  -d extension=ctype.so

composer.lock : composer.json lib/bin/composer
	$(COMPOSER_COMMAND) install --no-scripts
	sed -i -E 's/ +$$//' lib/composer/autoload_static.php
	touch $@

lib/bin/composer : lib/compinst.php
	$(PHP_COMMAND) $< $(COMPOSER_VERSION) $@
	chmod +x $@
	$@ --version
	touch $@

.PHONY : clean
clean :
	rm -f GITHUB_OUTPUT.cache
