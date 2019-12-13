#!/usr/bin/python
# This is Stratus-specific.
# Problems? Questions? Edits? ask Jackie or Jwise
# git clone https://github.com/jwisecarver-mm/migration_script && chmod +x ./migration_script/buildmig.py &&./migration_script/buildmig.py

import os
import sys

def check_stratus_database_credentials(stratus_database_user, stratus_database_name):
    if stratus_database_user.find("user_") == -1 or stratus_database_name.find("db_") == -1:
        print "Stratus CLI gave invalid DB user, is '/usr/share/stratus/cli database.config' working?"
        print
        stratus_database_user = raw_input("Please enter DB user: ")
        print
        stratus_database_name = raw_input("Please enter DB name: ")
        print
        stratus_database_password = raw_input("Please enter DB password: ")
        print

# Get database credentials.
print
print "Automatically fetching Stratus DB credentials..."
stratus_database_credentials = "/usr/share/stratus/cli database.config > mig.log 2>&1"
os.system(stratus_database_credentials)

stratus_database_user = os.popen("cat mig.log | grep Username | awk '{print $3}' | cut -c3- | rev | cut -c4- | rev").read()
stratus_database_name = os.popen("cat mig.log | grep Username | awk '{print $7}' | cut -c3- | rev | cut -c4- | rev").read()
stratus_database_password = os.popen("cat mig.log | grep Username | awk '{print $14}' | cut -c3- | rev | cut -c4- | rev").read()

# Clean up after ourselves..
os.system("rm mig.log")

stratus_database_user=stratus_database_user.replace('\n','')
stratus_database_name=stratus_database_name.replace('\n','')
stratus_database_password=stratus_database_password.replace('\n','')

check_stratus_database_credentials(stratus_database_user, stratus_database_name)

# Collect information from the user.
remote_ssh_user = raw_input("Remote SSH user: ")
remote_ssh_host = raw_input("Remote SSH host: ")
remote_ssh_port = raw_input("Remote SSH port: ")
remote_ssh_password = raw_input("Remote SSH password (just hit enter for SSH keypair): ")
remote_install_root = raw_input("Remote path: ")
local_install_root = raw_input("Local path: ")
new_base_url = raw_input("New base URL: ")

print
print "1. M1"
print "2. M2"
magento_version = raw_input("1 or 2: ")

if magento_version == '1':
    magento_version = "m1"
elif magento_version == '2':
    magento_version = "m2"
else:
    print
    print "Invalid input, start over."
    print
    sys.exit()

# Print the constructed migration command.
migcom = 'php ./migration_script/migrate.php --ssh_user={} --ssh_port={} --ssh_url="{}" --ssh_web_root={} --db_user={} --db={} --db_pass={} --web_root={} --base_url={} --magento={} --ssh_passwd={}'.format(remote_ssh_user, remote_ssh_port, remote_ssh_host, remote_install_root, stratus_database_user, stratus_database_name, stratus_database_password, local_install_root, new_base_url, magento_version, remote_ssh_password)
print
print migcom
print