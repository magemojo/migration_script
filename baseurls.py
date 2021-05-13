#!/usr/bin/python
#BASE URLS
#Problems? Questions? Edits? ask Jackie


# Needed things
import os
import sys
import os.path


# Some Pretty Colors for logs
NC='\033[0m' # No Color
RED='\033[0;31m'
GREEN='\033[0;32m'


# CHECK IF I CAN HAZ STRATUS DB CREDENTIALS
print("Grabbing stratus db credentials....")
stratCreds = "/usr/share/stratus/cli database.config > mig.log 2>&1"
os.system(stratCreds)

stratUser = os.popen("cat mig.log | grep Username | awk '{print $3}' | cut -c3- | rev | cut -c4- | rev").read()
stratDb = os.popen("cat mig.log | grep Username | awk '{print $7}' | cut -c3- | rev | cut -c4- | rev").read()
stratPass = os.popen("cat mig.log | grep Username | awk '{print $14}' | cut -c3- | rev | cut -c4- | rev").read()

stratUser=stratUser.replace('\n','')
stratDb=stratDb.replace('\n','')
stratPass=stratPass.replace('\n','')

print("stratUser = " + stratUser)
print("stratDb = " + stratDb)
print("stratPass = " + stratPass)
print(" ")


# Make sure our baseurls.txt is not empty
filesize = os.path.getsize("./baseurls.txt")
if filesize == 0:
   print(RED + "baseurls.txt not found or empty" + NC)
else:
   # Loop through found attacker IPs
   with open("./baseurls.txt") as f:
      contents = f.readlines()
      for line in contents:
         line = line.replace("\n", "")
         old,new = line.split(',')
         # Check line read haz the needful
         if (new == "") or (old == ""):
            print(RED + "This line is missing something.. skipping" + NC + line)
         else:
            # Query we will use for this line
            sql = "update core_config_data set value='" + new + "' where value='" + old + "'"
            # Lets do this thing
            do = "mysql -h mysql -u " + stratUser + " -p" + stratPass + " " + stratDb + " -e \"" + sql + "\""
            os.system(do)
            # Printing done thing
            print(sql)
