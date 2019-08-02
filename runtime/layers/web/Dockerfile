FROM nginx:latest
COPY default.conf /etc/nginx/conf.d/default.conf

CMD  sed -i "s|##DOCUMENT_ROOT##|$DOCUMENT_ROOT|g" /etc/nginx/conf.d/default.conf;sed -i "s|##HANDLER##|$HANDLER|g" /etc/nginx/conf.d/default.conf && nginx -g "daemon off;"
