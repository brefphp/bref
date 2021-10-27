ARG IMAGE

FROM ${IMAGE}

RUN yum install -y zip

WORKDIR /opt

RUN zip --quiet --recurse-paths /tmp/layer.zip .
