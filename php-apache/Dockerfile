# docker build -t lfrichter/php-apache-hpa .
# docker run -it lfrichter/php-apache-hpa bash
# docker push lfrichter/php-apache-hpa
FROM php:7-apache
COPY index.php /var/www/html/index.php
RUN chmod a+rx index.php