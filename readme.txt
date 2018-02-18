ECAD PHP fileviewer v0.2.04xx Handbook




Administrator: admin, admin

First Steps:
Copy index.php, to where you want to use the ECAD PHP file hub.
open it in your browser.
The program will then install all needed configurations, and a standard user.

Administratior: usr:admin pw:admin
Test user: usr:user0 pw:admin

The folder ECAD PHP fileviewer X data will be created, in it you can find the acces restiction file (.htaccess), and the folders users and shares.

The passwords that are saved, are the md5 sum of the password and the secret word "word".





Extended Configuration:

As default when uploading a file all special characters are replaced with "_". You can allow all characters by setting $allowAllCharactersInObjectNames to true inside the config.php file. (if you have used an older version prior to 0.2.03g you may have to add the line to the file)




Importend changes:
v0.2.04
New password save system (old users from version 0.2.03 can no longer login. copy the "update_parameters.php" file together with the new index.php file to reset the admin password to admin)
.....



All changes:
0.2.04xx
new session handling system
quick login system

New password save system
Added new user settings options in administrator Interface

0.2.03g
added the option to allow all characters in a file name (may cause some problems with older operating systems and older versions of php or apache)

0.2.03e-php7 compatible
made the program compatible with php7


0.2.03e
stopped development

0.2.03d
removed the my pages button as it will later be implemented in a new project.

0.2.03c
logging for multiple files upload added
implemented giving error when post size has exceeded system limit

0.2.03b
download as zip archive bug for administrator root viewer solved

0.2.03
select all objects checkbox added for user folder and shares
download all selected files added for user folder and shares

0.2.02
multiple file upload implemented
single file upload removed

0.2.01
sharing of folders between users implemented

0.1.19b
logging problems fixed
file upload is only confirmed when upload was really successful and no errors occurred

0.1.19
code cleanup

0.1.18b
problem fixed that when users are edited and password changed, that they get part of the permissions of the administrator
problem fixed that users get too many permissions when there configuration file doesn’t exist

0.1.18
filebrowser for administrator solved
deleting files for users fixed
funktion deleteDir removed

0.1.17g
security problem solved that administrators could leave the program folder
acces to the system folder can be manualy granted in the useconf file $canAccessSystemFolder

0.1.17f
file browser for administrator for the program files

0.1.17e
the administrator can download the log file
downloading the log file is logged

0.1.17d
deleting folders with files inside implemented

0.1.17c
logging for uploads
slight design change
deleting files implemented

v0.1.17b
upload system implemented

v0.1.16
logging system implemented

v0.1.15c
fixed downloads error

v0.1.15b
escape characters in file renaming are filtered

v0.1.15
files are Naturally sorted
new folders are numbered

v0.1.14
+ users can create folders
+ users can rename objects
- ASCII Encoding support has errors

v0.1.13
users are now saved in the users folder

v0.1.12
escape characters in folder request are filtered
users with no password no longer create errors after the logout

v0.1.11
users will no longer be logged out when edited if the password was not changed

v0.1.10
can upload and delete checkbox added
is admin button removed

v0.1.09
users sessions can be closed in the administration Interface

v0.1.08
in user edit menu you no longer can create new users

v0.1.07
admin can no longer be deleted
password of edited users is kept when field left empty

v0.1.06
User administration

v0.1.05
unique cookie authorization, all accepted cockis are saved in login.php

v0.1.03
new downloader with buffer for big files to download
sets filesze in header
sessin_start removed
path has now links to the folders
https support added
v0.1.03e
mount button removed