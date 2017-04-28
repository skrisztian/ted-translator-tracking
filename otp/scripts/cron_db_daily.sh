#!/bin/sh

echo [`date`] D + update_db_languages.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_languages.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + update_db_translators_main.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_translators_main.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + update_db_translator_no_hun.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_translator_no_hun.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + update_db_translators_ted_id.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_translators_ted_id.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + update_db_translator_names.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_translator_names.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + update_db_subtitles.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_subtitles.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + update_db_subtitles_ted_credit.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_subtitles_ted_credit.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + update_db_left_ted.php >> /home/archifa/otp/logs/cron.log
/usr/bin/php -f /home/archifa/otp/scripts/update_db_left_ted.php >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D + backup_db.sh >> /home/archifa/otp/logs/cron.log
/home/archifa/otp/scripts/backup_db.sh >> /home/archifa/otp/logs/cron.log 2>&1

echo [`date`] D +--- >> /home/archifa/otp/logs/cron.log