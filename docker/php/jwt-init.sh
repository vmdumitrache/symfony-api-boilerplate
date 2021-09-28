#!/bin/bash

mkdir -p /var/www/app/config/jwt

openssl genpkey \
	-out /var/www/app/config/jwt/private.pem \
    -aes256 \
    -algorithm rsa \
    -pass pass:app \
    -pkeyopt rsa_keygen_bits:4096

openssl pkey \
    -in /var/www/app/config/jwt/private.pem \
    -passin pass:app \
    -out /var/www/app/config/jwt/public.pem \
    -pubout

chmod 400 /var/www/app/config/jwt/public.pem /var/www/app/config/jwt/private.pem
chown www-data /var/www/app/config/jwt/public.pem /var/www/app/config/jwt/private.pem
