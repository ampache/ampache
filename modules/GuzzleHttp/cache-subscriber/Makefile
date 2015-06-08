all: clean coverage

test:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-html=artifacts/coverage
	open artifacts/coverage/index.html

view-coverage:
	open artifacts/coverage/index.html

clean:
	rm -rf artifacts/*
