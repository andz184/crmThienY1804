@echo off
cd /d %~dp0
php artisan schedule:run >> storage/logs/scheduler.log 2>&1
