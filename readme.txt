ECAD PHP fileviewer v0.1.15b Handbook

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