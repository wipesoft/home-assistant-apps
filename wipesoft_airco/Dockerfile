FROM php:8.4-fpm-alpine

ARG BUILD_VERSION
ARG BUILD_ARCH

LABEL \
    io.hass.version="${BUILD_VERSION}" \
    io.hass.type="app" \
    io.hass.arch="${BUILD_ARCH}"

RUN apk add --no-cache nginx \
    && mkdir -p /run/nginx

COPY app /app
COPY ingress.conf /etc/nginx/http.d/default.conf
COPY run.sh /run.sh

RUN chmod 0755 /run.sh

CMD ["/run.sh"]

