#!/usr/bin/python
#Migrate or copy
#USE ON STRATUS SERVER ONLY
#Problems? Questions? Edits? ask Jackie
#wget http://stuffandthings.magemojo.io/buildmig.py;chmod u+x buildmig.py;./buildmig.py

import os
import sys

###### FUNCTIONS START ######
#SANITY CHECK LOCAL STRATUS DB USER
def checkStratUser(stratUser):
  if stratUser.find("user_") == -1:
    print "\033[1;45m" + str("Stratus DB user doesn't seem right on this instance: Is '/usr/share/stratus/cli database.config' working?") + "\033[1;m"
    print "\033[1;45m" + str("example user: user_2matujnv3pm") + "\033[1;m"
    stratUser = raw_input("Please enter DB user > ")
    checkStratUser(stratUser)
#SANITY CHECK LOCAL STRATUS DB NAME
def checkStratDb(stratDb):
  if stratDb.find("db_") == -1:
    print "\033[1;45m" + str("Stratus DB name doesn't seem right on this instance: Is '/usr/share/stratus/cli database.config' working?") + "\033[1;m"
    print "\033[1;45m" + str("example user: db_2matujnv3pm") + "\033[1;m"
    stratDb = raw_input("Please enter DB name > ")
    checkStratDb(stratDb)
###### FUNCTIONS END ######

#CHECK IF I CAN HAZ STRATUS DB CREDENTIALS
print "Grabbing stratus db credentials...."
stratCreds = "/usr/share/stratus/cli database.config > mig.log 2>&1"
os.system(stratCreds)

stratUser = os.popen("cat mig.log | grep Username | awk '{print $3}' | cut -c3- | rev | cut -c4- | rev").read()
stratDb = os.popen("cat mig.log | grep Username | awk '{print $7}' | cut -c3- | rev | cut -c4- | rev").read()
stratPass = os.popen("cat mig.log | grep Username | awk '{print $14}' | cut -c3- | rev | cut -c4- | rev").read()

stratUser=stratUser.replace('\n','')
stratDb=stratDb.replace('\n','')
stratPass=stratPass.replace('\n','')

print "stratUser = " + stratUser
print "stratDb = " + stratDb
print "stratPass = " + stratPass
print " "

checkStratUser(stratUser)
checkStratDb(stratDb)

#ASK FOR LOCAL PATH
stratPath = raw_input("Stratus path destination? > (/srv/public_html/)") or "/srv/public_html/"

#ASK FOR REMOTE SERVER INFO
remUser = raw_input("Remote ssh user ? > ")
remServer = raw_input("Remote server hostname or ip ? > ")
remPort = raw_input("Remote ssh port ? > (22)") or "22"
remPath = raw_input("Remote path ? > ")
print " "
print "1. M1"
print "2. M2"

mtype = raw_input("1 or 2? > ")

if mtype == '1':
  remVer = "m1"
elif mtype == '2':
  remVer = "m2"

#ASK FOR NEW BASE URL
stratUrl = raw_input("New base URL ? > ")

#BUILD MIGRATE COMMAND
migcom = "php ./migrate.php --ssh_user=" + remUser + " --ssh_port=" + remPort + " --ssh_url=\"" + remServer + "\" --ssh_web_root=" + remPath + " --db_user=" + stratUser + " --db=" + stratDb + " --db_pass=" + stratPass + " --web_root=" + stratPath + " --base_url=" + stratUrl + " --magento=" + remVer
print " "
print migcom
