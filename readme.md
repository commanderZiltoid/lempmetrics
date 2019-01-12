index.php shows example usage

access.log functionality depends on your access.log files using the following format:

``
log_format main '["$remote_addr","$time_local","$request","$status","$request_time","$bytes_sent","$http_referer","$http_user_agent"]';
access_log /var/log/nginx/access.log main;
``