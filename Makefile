COMPOSER = $(shell if composer --version > /dev/null 2> /dev/null; then \
		echo 'composer';\
	elif [ -f composer.phar ]; then \
		echo 'php composer.phar';\
	else \
		echo 'error';\
	fi)
FILES_TO_RELEASE = vendor composer.json composer.lock languages \
				php qingstor.php readme*.txt LICENSE

.PHONY: help
help:
	@echo "Please use \`make <target>\` where <target> is one of"
	@echo "  build    to build the plugin"
	@echo "  release  to build and release the plugin"

.PHONY: build
build:
ifeq (${COMPOSER}, error)
	$(error Please install composer.)
endif
	${COMPOSER} install

.PHNOY: release
release: build
	mkdir -p "release"
	zip -r "release/wp-qingstor.zip" ${FILES_TO_RELEASE}

.PHONY: clean
clean:
	rm -rf ${CURDIR}/vendor ${CURDIR}/composer.lock

