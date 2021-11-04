ARG IMAGE

FROM ${IMAGE} as src

FROM alpine:3.14

RUN apk add zip

COPY --from=src /opt /opt

WORKDIR /opt

RUN zip --quiet --recurse-paths /tmp/layer.zip .
