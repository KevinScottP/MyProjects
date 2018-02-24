#!/bin/bash
# MySQL backup scripted
# This will back up all data on mysql
# Backup cron run at /etc/cron.d/backup

#local varables
backup_dir="/var/backups/mysql"
filename="${backup_dir}/mysql-`hostname`-`eval date +%Y%m%d`.sql.gz"
backup_log="/var/log/mysqlBackup.log"
pw=""

#make backup file
if [ ! -d "$backup_dir" ]; then
        mkdir -p $backup_dir
        touch $filename
fi
#make backup log
touch $backup_log
# Dump the entire MySQL database
/usr/bin/mysqldump -u root -p$pw --all-databases | gzip > $filename
# Delete backups older than 10 days
find $backup_dir -ctime +10 -type f -delete
echo "Nightly Backup Successful: $(date)" >> $backup_log
