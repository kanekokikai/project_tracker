@echo off
REM Copy to deploy.config.bat and fill password.
REM FTP account connection dir must be:
REM   kanekokikai-app.com/public_html/project.kanekokikai-app.com

set FTP_HOST=sv16374.xserver.jp
set FTP_USER=projectdeploy@project.kanekokikai-app.com
set FTP_PASS=YOUR_PASSWORD
set REMOTE_DIR=/
set FTP_PROTOCOL=ftpes
