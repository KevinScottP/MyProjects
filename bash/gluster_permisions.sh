#!/bin/bash

# Make sure only root can run our script
if [ "$(id -u)" != "0" ]; then
   echo "You need to be 'root'" 1>&2
   exit 1
fi

#server02
setfacl -R -m g:0000:rx /path #fill in group name (0000) and path
setfacl -R -m u:0000:rwx /path #fill in client name (0000) and path
#server12
setfacl -R -m g:0000:rx /path #fill in group name (0000) and path
setfacl -R -m u:0000:rwx /path #fill in client name (0000) and path
#server13
setfacl -R -m g:0000:rx /path #fill in group name (0000) and path
setfacl -R -m u:0000:rwx /path #fill in client name (0000) and path

