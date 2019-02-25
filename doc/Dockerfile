FROM composer:1.8.2

RUN apk add --no-cache --virtual graphviz && \
    apk add --no-cache --repository http://dl-3.alpinelinux.org/alpine/edge/testing gnu-libiconv

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

RUN composer global require hirak/prestissimo

WORKDIR /u/app

ADD . .

RUN composer install --prefer-dist

CMD ./vendor/bin/phpdoc -d /u/src --ignore */vendor/*
