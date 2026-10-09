# Dockerfile — la misma pila de XAMPP (Apache + PHP), pero en un contenedor
FROM php:8.2-apache

# PDO para MySQL (cURL ya viene incluido en esta imagen)
RUN docker-php-ext-install pdo_mysql

# Apache escucha en el puerto que indique el proveedor (variable PORT); por defecto, 80
ENV PORT=80
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Apache tiene que cargar un solo MPM: prefork (el que necesita PHP).
# En Railway puede aparecer otro más y Apache no arranca ("More than one MPM loaded"),
# así que se quitan los demás al construir y otra vez al arrancar.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork

COPY . /var/www/html/
# para que avisos.log (modo simulación) se pueda escribir
RUN chown -R www-data:www-data /var/www/html/api

CMD ["sh", "-c", "rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; exec apache2-foreground"]
