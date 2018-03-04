#!/bin/bash
set -e
set -u

cert_dir=/etc/letsencrypt/live/$VIRTUAL_HOST
# if $cert_dir is empty or does not exist
if ! [ "$(ls -A $cert_dir 2> /dev/null)" ]; then
    echo "No SSL certificate found in /etc/letsencrypt/. Generating self-signed..."
    mkdir -p $cert_dir
    cd $cert_dir
    subject="/C=NL/ST=Amsterdam/O=Geant/localityName=Amsterdam/commonName=$VIRTUAL_HOST/organizationalUnitName=/emailAddress=/"
    openssl genrsa -out privkey.pem 2048
    openssl req -new -subj $subject -key privkey.pem -out csr
    openssl x509 -req -days 1000 -in csr -signkey privkey.pem -out cert.pem
    echo " " > /etc/letsencrypt/live/$VIRTUAL_HOST/chain.pem
fi

# start all the services
/usr/local/bin/supervisord -n
