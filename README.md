# migration_script
For migrating Magento sites via SSH to Stratus.

Requires rsync and mysqldump on the remote host to work.

# Notes
You do not need to flush the local (Stratus) db or filesystem, the script should take care of it.

This is a pull-based tool. Clone it to the DESTINATION server/host where everything will be copied TOO (usually the new/fresh/target instance).

The variable names in init.sh that you need to edit are very self-descriptive (remote, local, etc):

```
# This is the remote SSH user.
_remote_user=jwisecarver

# This is the remote SSH address.
_remote_ssh_url="123.123.123.123"

# This is the remote SSH port.
_remote_ssh_port=22

# This is the remote SSH user's password. You can leave it exactly as is if you do not need it (ie. ssh keypair).
_remote_ssh_password=""

# This is the REMOTE magento installation root (source).
_remote_web_root=/srv/public_html/

# This is the LOCAL magento installation root (destination).
_local_web_root=/srv/public_html/

# This is the new URL for the base store (other stores will not be updated).
_base_url=https://uuid.mojostratus.io/

# This is the version of Magento we're working with (m1 or m2).
_magento_version=m1
```

# Setting up
1) Clone and set permissions on init.sh:
```git clone https://github.com/magemojo/migration_script && chmod +x ./migration_script/init.sh```

2) Review/edit lines 2 through 8 in init.sh with an editor of your choice:
```vim ./migration_script/init.sh```

3) Run it.
```./migration_script/init.sh```

# Report issues or suggestions you have, please.