ECAD PHP fileviewer v0.2.03 Handbook

Administrator: admin, admin

First Steps:
copy index.php, to where you want to use ECAD PHP fileviewer.
open it in your browser.
The system will than install all needed configurations, and a standard user.

The Folders and document a user can see are under /ECAD PHP fileviewer X data/users/user_name/data
Upload your folders there so the user can access and download them

The folder ECAD PHP fileviewer X data will be created, in it you can find the acces restiction file (.htaccess), the folder users and shares.

To administrate login as admin admin. change your password!

On the main page you can now login as user0 with the password admin.
The accesseble files of the users are under /ECAD PHP fileviewer X data/users/user0/data.

The passwords that are saved, are the md5 sum of the password and the secret word „word“.

changes:
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
sessin_start entfernt
path hat jetzt links zu den Ordnern
https support