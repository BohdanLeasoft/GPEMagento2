#!/bin/bash
DOMAIN=${1:-magento2.test}
VERSION=${2:-2.2.6}

curl -s https://raw.githubusercontent.com/markoshust/docker-magento/19.0.0/lib/template|bash -s - magento-2
bin/download $VERSION
echo "127.0.0.1 $DOMAIN" | sudo tee -a /etc/hosts
bin/start
bin/setup $DOMAIN
