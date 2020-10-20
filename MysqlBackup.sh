#!/bin/bash
user='jos123'
password='jos123'
database='pay'
dir='/www/data/mysql_backup'

cd $dir
mv $database.sql.gz $database.old.sql.gz

mysqldump -u$user -p$password -B -F -R --single-transaction --master-data=2 --default-character-set=utf8 $database|gzip >./$database.sql.gz