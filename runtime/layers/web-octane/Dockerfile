FROM nginx:alpine
COPY default.conf /etc/nginx/conf.d/default.conf

CMD  if [ -z "$DOCUMENT_ROOT" ];then HANDLER_DR=$HANDLER;else HANDLER_DR=${HANDLER#"$DOCUMENT_ROOT/"};fi;\
if [ "$SERVER" == "swoole" ];then DEBUG_SERVER=debug;else DEBUG_SERVER=octane;fi;\
sed -i "s|##DOCUMENT_ROOT##|$DOCUMENT_ROOT|g" /etc/nginx/conf.d/default.conf\
;sed -i "s|##HANDLER##|$HANDLER|g" /etc/nginx/conf.d/default.conf\
;sed -i "s|##HANDLER_DR##|$HANDLER_DR|g" /etc/nginx/conf.d/default.conf\
;sed -i "s|##DEBUG_SERVER##|@$DEBUG_SERVER|g" /etc/nginx/conf.d/default.conf\ 
&& nginx -g "daemon off;"
