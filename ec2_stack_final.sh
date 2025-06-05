#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "Updating system and installing essential packages..."
sudo yum update -y
sudo yum install -y git curl unzip gcc-c++ make epel-release

echo "Installing Apache, PHP, and extensions..."
sudo amazon-linux-extras enable php7.4
sudo yum clean metadata
sudo yum install -y php php-mbstring php-xml php-mysqlnd php-fpm httpd

echo "Starting and enabling Apache..."
sudo systemctl start httpd
sudo systemctl enable httpd

echo "Installing Node.js and npm..."
curl -sL https://rpm.nodesource.com/setup_16.x | sudo bash -
sudo yum install -y nodejs

echo "Installing PM2 globally..."
sudo npm install -g pm2

echo "Cloning PHP (CodeIgniter) backend..."
cd /var/www
sudo git clone https://github.com/vndamodar/qapin_backend_v2.0.git
sudo chown -R apache:apache /var/www/qapin_backend_v2.0
sudo chmod -R 755 /var/www/qapin_backend_v2.0

echo "Injecting custom common_helper.php..."
if [ -f "./common_helper.php" ]; then
  sudo cp ./common_helper.php /var/www/qapin_backend_v2.0/application/helpers/common_helper.php
  sudo chown apache:apache /var/www/qapin_backend_v2.0/application/helpers/common_helper.php
  sudo chmod 644 /var/www/qapin_backend_v2.0/application/helpers/common_helper.php
  echo "common_helper.php injected successfully."
else
  echo "common_helper.php not found. Please upload it to the same directory as this script before running."
  exit 1
fi

echo "Restarting Apache after PHP backend setup..."
sudo systemctl restart httpd

echo "Cloning Node.js backend..."
cd /var/www
sudo git clone https://github.com/vndamodar/node_1.O.git
cd node_1.O
npm install
pm2 start server.js --name node_1.O
pm2 save
pm2 startup systemd | grep sudo | bash

echo "Cloning and building Vue.js frontend..."
cd /var/www
sudo git clone https://github.com/vndamodar/Vue-Latest.git
cd Vue-Latest
npm install
npm run build

echo "Creating admin-legacy directory and index.php..."
sudo mkdir -p /var/www/html/admin-legacy
echo "<?php echo \"Server Ready\"; ?>" | sudo tee /var/www/html/admin-legacy/index.php > /dev/null
sudo chown apache:apache /var/www/html/admin-legacy/index.php
sudo chmod 644 /var/www/html/admin-legacy/index.php

echo "Installing Certbot and generating SSL certificates..."
# sudo yum install -y certbot python2-certbot-apache
# sudo certbot --apache -d admin-legacy.qapin.com -d www.admin-legacy.qapin.com
# sudo certbot --apache -d api.qapin.com

echo "Installing custom Apache vhost config for admin-legacy..."
sudo cp ./admin-legacy.qapin.com.conf /etc/httpd/conf.d/admin-legacy.qapin.com.conf

echo "Installing custom Apache vhost config for api..."
sudo cp ./api.qapin.com.conf /etc/httpd/conf.d/api.qapin.com.conf

echo "Reloading Apache with updated configs..."
sudo systemctl restart httpd

echo "Setup complete. Your PHP, Node.js, and Vue.js apps are deployed!"
