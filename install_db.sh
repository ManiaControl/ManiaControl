#!/bin/bash

read -e -p "Enter database name: " -i "maniacontrol_db" DATABASE_NAME
read -e -p "Enter user name: " -i "maniacontrol" USER_NAME
read -s -p "Enter user password: " USER_PASSWORD
echo
echo

# Preparation
SQL_FILE_NAME="install_db.sql"
rm -f ${SQL_FILE_NAME}

echo "Creating SQL script ..."
echo "CREATE DATABASE ${DATABASE_NAME} DEFAULT CHARACTER SET 'utf8mb4' DEFAULT COLLATE 'utf8mb4_unicode_ci';" >> ${SQL_FILE_NAME}
echo "CREATE USER '${USER_NAME}'@'localhost' IDENTIFIED BY '${USER_PASSWORD}';" >> ${SQL_FILE_NAME}
echo "GRANT ALL PRIVILEGES ON ${DATABASE_NAME}.*  TO '${USER_NAME}'@'localhost';" >> ${SQL_FILE_NAME}

echo "Executing SQL script as root ..."
sudo mysql -u root -p < ${SQL_FILE_NAME}

echo "Cleaning up ..."
rm ${SQL_FILE_NAME}

echo "Done."
