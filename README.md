# migration_script
For migrating Magento sites via SSH to Stratus


Example command - all these are required, and it prompts for SSH login twice. Needs improved and no arg checking at the moment.
```
php migrate.php --ssh_user=super --ssh_port=22 --ssh_url="192.190.220.52" --ssh_web_root=/home/super/magento1super.magemojo.io/ --db_user=user_cded1u2ypqu --db=db_cded1u2ypqu --db_pass=48077d8f-0a43-4ab8-a556-2b9ad201b5ba --web_root=/srv/public_html/ --base_url=https://cded1u2ypquuejdj.mojostratus.io/  --magento=m1
```

# buildmig.py
Pulls dbase creds of instance you are on and asks you for source instance info in order to build the above mig script command for you. Does not run it. Only outputs it to screen for you.  

# baseurls.py and baseurls.txt
Use this to change base urls in dev copies that have multiple. 

1. Set up baseurls.txt one time and use it for all future dev copy refreshes. This text file needs the url to replace and the new url like so:
```
https://liveurl.com/,https://devuuid.mojostratus.io/
https://liveurl2.com/,https://devuuid2.mojostratus.io/
```
(one set per line)

2. Run baseurls.py
```
python3 ./baseurls.py
```
3. Leave it under migration_script so you can use it next time. 
