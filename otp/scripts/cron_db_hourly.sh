#!/bin/sh

echo [`date`] ">>> Skipping hourly:" >> /home/archifa/otp/logs/cron.log
exit 2

# echo [`date`] ">>> Starting hourly:" >> /home/archifa/otp/logs/cron.log
# /usr/bin/php -f /home/archifa/otp/scripts/update_db_tasks.php >> /home/archifa/otp/logs/cron.log 2>&1
# /usr/bin/php -f /home/archifa/otp/scripts/update_db_activity_subt_task.php >> /home/archifa/otp/logs/cron.log 2>&1
# echo [`date`] "--- finished" >> /home/archifa/otp/logs/cron.log
