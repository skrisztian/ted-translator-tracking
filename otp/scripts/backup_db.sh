#!/bin/sh

# --- Set parameters ---

# Current date and time
now="$(date +'%Y%m%d_%H%M%S')"

# Directories
backupfolder="/home/archifa/otp/backups"
logfilefolder="/home/archifa/otp/logs"

# Files
basefilename="archifa_otp_backup_$now".gz
backupfile="$backupfolder/$basefilename"
configfile="$configdir/otp.ini"
logfile="$logfilefolder/db_backup.log"

# Database
db_name="$(grep script_db_name ../otp.ini | awk '{print $3}' | sed 's/"//g')"
db_user="$(grep script_db_user ../otp.ini | awk '{print $3}' | sed 's/"//g')"
db_password="$(grep script_db_password ../otp.ini | awk '{print $3}' | sed 's/"//g')"

# --- Execute the job ---

# Backup the database
echo "$(date) +++ Starting backup on $db_name" >> "$logfile"
/usr/bin/mysqldump --user="$db_user" --password="$db_password" --default-character-set=utf8 "$db_name" | gzip > "$backupfile"  2>> "$logfile"
rc=$?
echo "$(date) mysqldump finished with exit code $rc" >> "$logfile"

# Cleanup files older than 8 days
find "$backupfolder" -name archifa_otp_backup_* -mtime +8 -exec rm {} \;
echo "$(date) deleted old backup files" >> "$logfile"
echo "$(date) --- Finished backup" >> "$logfile"

# --- Exit script ---
exit 0