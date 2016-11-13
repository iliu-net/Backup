# Backup

[Backup] (https://github.com/iliu-net/Backup/) is a
Wordpress plugin that makes backups of your Wordpress
site locally or to Google Drive.

## Description

Backup is a plugin that provides backup capabilities for
Wordpress. Backups are `zip` archives created locally
and uploaded to a folder of your choosing on Google
Drive.

You are in total control of what files and directories
get backed up.

## Features

- Schedule automatic backups.
- Back up the database.
- Back up files and directories.
- Fine grained control over what gets backed up.
- Store backups locally and/or on Google Drive.
- Interrupted uploads to Google Drive automatically resume.
- Get email notifications when something goes wrong.
- The settings page interface uses standard WordPress
  elements to fit right in.
- Extensive contextual help included.
- Advanced options are provided to control the inner
  workings of the plugin.

## Installation

The plugin requires WordPress and is installed like any
other plugin.

- Upload the plugin to the `/wp-contents/plugins/` folder.
- Activate the plugin from the 'Plugins' menu in WordPress.
- Configure the plugin by following the instructions on the `Backup` settings page.

If you need support configuring the plugin click on the
`help` button on the top right of the settings page.

## Contributors

- [Sorin Iclanzan](https://github.com/iclanzan) - Code, Idea, Design
- [Sergey Yakovlev](https://github.com/sergeyklay) - Localization into Russian, Code
- [Alejandro Liu](https://github.com/alejandroliu) - Forked from *2.2*.


## Changelog

**2.3.0** FORKED!
- Forked!
- Added support for multisite.
- Updated the Google Drive interface to use the official Google API PHP client.

**2.2**
- I've included some constants that allow you to predefine options for the backup plugin in the `wp-config.php` file. These are `BACKUP_REFRESH_TOKEN`, `BACKUP_DRIVE_FOLDER`, `BACKUP_CLIENT_ID`, `BACKUP_CLIENT_SECRET` and `BACKUP_LOCAL_FOLDER`.
- Modified the plugin to use the `uninstall.php` file to clean up after itself instead of hooking to the deactivation hook. This means that you won't lose your settings if you just deactivate the plugin. Also the refresh token is not revoked anymore upon uninstalling.
- Fixed a serious bug that allowed existing local folders to be used to store backups, resulting in the deletion of all files inside of it when disabling the plugin.
- Optimized the plugin to use the entire execution time available.
- Other small fixes.

**2.1.3**
- Optimized the archiving function.

**2.1.2**
- Fixed a bug that was causing Backup to not work on most shared hosts.
- Fixed a bug that was causing the initial backup directory not to be created.

**2.1**
- Added field to enter specific paths to include in the backup.
- Added ability to specify the day of the week and and the time when to schedule the first backup.
- Outputting progress when doing manual backup.
- Now generating separate log files for each backup.
- Added a unique token to the manual backup URI so that backups can't be deployed by anyone.
- The default backup folder name contains the unique token and is web inaccessible.
- Displaying user information for the currently authorized account. This is useful in case of multiple Google accounts when the user doesn't remember which account was authorized.
- Added option to manually enter the refresh token in case you already authorized your account on another website using this plugin.
- You can now give backup archives a custom title.
- Added option to control the maximum resume attempts.
- Added option to change the request timeout value.
- Added option to disable specific HTTP transports.
- Added option to disable SSL verification against a certificate.
- Added the ability to manually enter the refresh token in case you want to use the same Client ID for more than one WordPress site.
- Added Russian localization.
- The plugin now properly handles updates. This means you won't have to reactivate the plugin after each update.
- You can now set a time limit of 0 which will cause the backup process to run as much as it needs to. This is not recommended though.
- Added a 'Need help?' button next to the title of the Settings page which opens the context help. Hopefully people will stop asking questions that are already covered there.
- Dates are now localized.
- Fixed a bug that was causing archives to have duplicate files when using PclZip.

**2.0.1**
- Fixed database dump not getting added to the backup archive in some circumstances.
- Fixed not setting the time limit and chunk size when resuming uploads.
- Fixed local backups not being deleted after a failed upload.
- Fixed some bugs in the GDocs class and optimized chunk reading.
- Now logging upload speeds.
- Other minor bug fixes.

**2.0**
- Rewrote 95% of the plugin to make it more compatible with older PHP versions, more portable and cleaner. It now uses classes and functions already found in WordPress where possible.
- Interrupted backup uploads to Google Drive will resume automatically on the next WordPress load.
- Added internationalization support. If anyone wishes to translate the plugin feel free to do so.
- Revamped the settings page. You can now choose between one and two column layout. Added meta boxes that can be hidden, shown or closed individually as well as moved between columns.
- Added contextual help on the settings page.
- Added ability to select which WordPress directories to backup.
- Added ability to exclude specific files or directories from being backed up.
- Added option not to backup the database.
- Displaying used quota and total quota on the settings page.
- Changed the manual backup URI so that it now works for WordPress installations where pretty permalinks are disabled.
- Optimized memory usage.
- Added PclZip as a fallback for creating archives.
- Can now configure chunk sizes for Google Drive uploads.
- Added option to set time limit when uploading to Google Drive.
- You can now view the log file directly inside the settings page.

**1.1.5**
- You can now chose not to upload backups to Google Drive by entering `0` in the appropriate field on the settings page.
- Resumable create media link is no longer hard coded.

**1.1.3**
- Fixed some issues created by the `1.1.2` update.

**1.1.2**
- Added the ability to store a different number of backups locally then on Google Drive.
- On deactivation the plugin deletes all traces of itself (backups stored locally, options) and revokes access to the Google Account.
- Fixed some more frequency issues.

**1.1.1**
- Fixed monthly backup frequency.

**1.1**
- Added ability to backup database. Database dumps are saved to a `sql` file in the `wp-content` folder and added to the backup archive.
- Added a page ( `/backup/` ) which can be used to trigger manual backups or used in cron jobs.
- Added ability to store a maximum of `n` backups.
- Displaying dates and times of last performed backups and next scheduled backups on the settings page as well as a link to download the most recent backup and the URL for doing manual backups (and cron jobs).
- Created a separate log file to log every action and error specific to the plugin.
- Cleaned the code up a bit and added DocBlock.

**1.0**
- Initial release.

## NOTES

- Add restore functionality
- See how to do resume
