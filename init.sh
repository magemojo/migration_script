# Config Options.
_remote_user=jwisecarver
_remote_ssh_port=22
_remote_ssh_url="123.123.123.123"
_remote_web_root=/srv/public_html/
_local_web_root=/srv/public_html/
_base_url=https://uuid.mojostratus.io/
_magento_version=m1

# Let Stratus CLI get the db credentials for you or you can put some in manually.
# IE: _local_db_user=foobar
/usr/share/stratus/cli database.config > cred.log 2>&1
_local_db_user=$(cat cred.log | grep Username | awk '{print $3}' | cut -c3- | rev | cut -c4- | rev)
_local_db_name=$(cat cred.log | grep Username | awk '{print $7}' | cut -c3- | rev | cut -c4- | rev)
_local_db_pass=$(cat cred.log | grep Username | awk '{print $14}' | cut -c3- | rev | cut -c4- | rev)
rm cred.log

php migrate.php --ssh_user=$_remote_user --ssh_port=$_remote_ssh_port --ssh_url=$_remote_ssh_url --ssh_web_root=$_remote_web_root --db_user=$_local_db_user --db=$_local_db_name --db_pass=$_local_db_pass --web_root=$_local_web_root --base_url=$_base_url --magento=$_magento_version
