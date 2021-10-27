FROM alpine:3.14

RUN apk add composer

ENTRYPOINT /usr/bin/tail

CMD ['-f', '/dev/null']