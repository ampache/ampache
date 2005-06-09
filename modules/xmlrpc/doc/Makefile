WEB=/var/www/xmlrpc/doc

all: index.html

index.html: xmlrpc_php.sgml
	jade -t sgml -d custom.dsl xmlrpc_php.sgml 

clean:
	rm -f *.html

install:
	mkdir -p ${WEB}
	cp *.html ${WEB}

web:
	mkdir -p ${WEB}
	cp *.html ${WEB}
