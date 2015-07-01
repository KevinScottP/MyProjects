#!/bin/bash
# This is a script file to make file folders for gluster.
# run as a root

mnt="/path/"
biz_path="myPath/"

# Make sure only root can run our script
if [ "$(id -u)" != "0" ]; then
  echo "You need to be 'root'" 1>&2
  exit 1
fi

echo "This script only works with the correct user names, so for only one server.";

read -p "Enter the storage file name: " install_file_names
install_file_names="$mnt$biz_path$install_file_names"

read -p "Enter the directory name: " dirName

read -p "Enter the directory size limit in (GB): " dirSize
dirSize="$dirSize""GB"

#loop for 25 time
COUNTER=0
while [  $COUNTER -lt 25 ]; do

  #generate random name
  random=$(< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c32)

  path="/$biz_path$dirName/$random"
  full_path="$mnt$biz_path$dirName/$random"

  #check for existing directory
  if [ -d "$full_path" ]; then

    echo "Repete Directory $full_path";
    #repete password
    let COUNTER=COUNTER

  else

    #write the path to a file
    echo $biz_path$dirName"/"$random"/" >> $install_file_names

    mkdir -p $full_path

    gluster volume quota bizuno-volume limit-usage $path $dirSize

    #For each server add the web user to each directory
    #srv02
    setfacl -R -m g:0000:rx $full_path #fill in group name (0000)
    setfacl -R -m u:0000:rwx $full_path #fill in client name (0000)

    #srv12
    setfacl -R -m g:0000:rx $full_path #fill in group name (0000)
    setfacl -R -m u:0000:rwx $full_path #fill in client name (0000)

    #srv13
    setfacl -R -m g:0000:rx $full_path #fill in group name (0000)
    setfacl -R -m u:0000:rwx $full_path #fill in client name (0000)

    let COUNTER=COUNTER+1

  fi
done

echo "Folders have been created successfully."
exit 0

