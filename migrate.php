<?php
//GLOBALS
$globals = array(
   "mysql_host" => "mysql",
   "prod_dump" => "/srv/prod_dump.sql"
);

//set up PHP ARG Options


$shortopts='';
$longopts  = array(
    "db:",
    "db_pass:",
    "db_user:",
    "ssh_user:",
    "ssh_url:",
    "ssh_port:",
    "ssh_web_root:",
    "web_root:",
    "base_url:",
    "magento:"
     // Required value
);
$options = getopt($shortopts,$longopts);
print_r($options);
//classes

class SimpleXMLExtended extends SimpleXMLElement {
    public function addCData($cData_text) {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDataSection($cData_text));
    }
}

function return_redis_config() {
    return [
        'cache' => [
            'frontend' => [
                'default' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'redis-config-cache',
                        'database' => '0',
                        'port' => '6381'
                    ],
                ],
                'page_cache' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'redis',
                        'port' => '6379',
                        'database' => '1',
                        'compress_data' => '0',
                    ],
                ],
            ],
        ]
    ];
}

function load_env_m2($env_path){
    try {
        $env_data = include $env_path;
        } catch (\Exception $e) {
        throw new \Exception("Could not open env.php" . $e);
        exit(1);
    }
    return $env_data;
}

function get_remote_db_info_m2($env_path){
    try {
        $env_data = include $env_path;
    } catch (\Exception $e) {
        throw new \Exception("Could not open env.php" . $e);
        exit(1);
    }
    $remote_db_info  = array(
        'db'=> $env_data['db']['connection']['default']['dbname'],
        'db_user'=> $env_data['db']['connection']['default']['username'],
        'db_pass' => $env_data['db']['connection']['default']['password'],
        'db_host' => $env_data['db']['connection']['default']['host'],
        'table_prefix' => $env_data['db']['table_prefix']
    );
    return $remote_db_info;
}

function get_remote_db_info_m1($local_xml_path) {
  $new_xml=file_get_contents($local_xml_path);
  $xml=new SimpleXMLExtended($new_xml);
  //zero out the field, not elegant but works
  $db=$xml->global->resources->default_setup->connection->dbname;
  $db_user=$xml->global->resources->default_setup->connection->username;
  $db_pass=$xml->global->resources->default_setup->connection->password;
  $db_host=$xml->global->resources->default_setup->connection->host;
  $table_prefix=$xml->global->resources->db->table_prefix;

  $remote_db_info  = array(
  'db'=> $db,
  'db_user'=> $db_user,
  'db_pass' => $db_pass,
  'db_host' => $db_host,
  'table_prefix' => $table_prefix
  );
  return $remote_db_info;
}

function set_db_creds_m2($env_data,$options) {
          $env_data['db']['connection']['default']['dbname']=$options['db'];
          $env_data['db']['connection']['default']['username']=$options['db_user'];
          $env_data['db']['connection']['default']['password']=$options['db_pass'];
          $env_data['db']['connection']['default']['host']='mysql';
          return $env_data;
}


function update_local_xml_m1($options,$local_xml_path) {
  $new_xml=file_get_contents($local_xml_path);
  $xml=new SimpleXMLExtended($new_xml);
  //zero out the field, not elegant but works
  echo "Configurating local.xml with DB info, redis, and memcache...";
  //database createCDataSection
  $db_user=$xml->global->resources->default_setup->connection->dbname='';
  $db_user=$xml->global->resources->default_setup->connection->username='';
  $db_pass=$xml->global->resources->default_setup->connection->password='';
  $db_host=$xml->global->resources->default_setup->connection->host='';
  $db_user=$xml->global->resources->default_setup->connection->dbname->addCData($options['db']);
  $db_user=$xml->global->resources->default_setup->connection->username->addCData($options['db_user']);
  $db_pass=$xml->global->resources->default_setup->connection->password->addCData($options['db_pass']);
  $db_host=$xml->global->resources->default_setup->connection->host->addCData('mysql');
  //Redis and redis sessions
  $xml->global->session_save='';
  $xml->global->session_save->addCData("db");
  $xml->global->session_save_path='';
  $xml->global->redis_session->host='redis-session';
  $xml->global->redis_session->port='6380';
  $xml->global->redis_session->password='';
  $xml->global->redis_session->timeout='2.5';
  $xml->global->redis_session->persistent='';
  $xml->global->redis_session->db='0';
  $xml->global->redis_session->compression_threshold='2048';
  $xml->global->redis_session->compression_lib='gzip';
  $xml->global->redis_session->log_level='1';
  $xml->global->redis_session->max_concurrency='20';
  $xml->global->redis_session->break_after_frontend='30';
  $xml->global->redis_session->fail_after='10';
  $xml->global->redis_session->break_after_adminhtml='30';
  $xml->global->redis_session->first_lifetime='600';
  $xml->global->redis_session->bot_first_lifetime='60';
  $xml->global->redis_session->disable_locking='1';
  $xml->global->redis_session->min_lifetime='60';
  $xml->global->redis_session->max_lifetime='2592000';


   //redis cache
  $xml->global->cache->backend_options->server='';
  $xml->global->cache->backend_options->server->addCData("redis");
  $xml->global->cache->backend_options->port='';
  $xml->global->cache->backend_options->port->addCData("6379");
  $xml->global->cache->backend_options->persistent='';
  $xml->global->cache->backend_options->persistent->addCData("");
  $xml->global->cache->backend_options->database='';
  $xml->global->cache->backend_options->database->addCData("2");
  $xml->global->cache->backend_options->password='';
  $xml->global->cache->backend_options->password->addCData("");
  $xml->global->cache->backend_options->connect_retries='';
  $xml->global->cache->backend_options->connect_retries->addCData("1");
  $xml->global->cache->backend_options->read_timeout='';
  $xml->global->cache->backend_options->read_timeout->addCData("10");
  $xml->global->cache->backend_options->automatic_cleaning_factor='';
  $xml->global->cache->backend_options->automatic_cleaning_factor->addCData('');
  $xml->global->cache->backend_options->compress_data='';
  $xml->global->cache->backend_options->compress_data->addCData('1');
  $xml->global->cache->backend_options->compress_tags='';
  $xml->global->cache->backend_options->compress_tags->addCData('1');
  $xml->global->cache->backend_options->compress_threshold='';
  $xml->global->cache->backend_options->compress_threshold->addCData('20480');
  $xml->global->cache->backend_options->compress_lib='';
  $xml->global->cache->backend_options->compress_lib->addCData('gzip');
  $xml->global->cache->backend_options->use_lua='';
  $xml->global->cache->backend_options->use_lua->addCData('0');
  $xml->global->cache->backend='';
  $xml->global->cache->backend->addCData('Cm_Cache_Backend_Redis');


  $xml->asXml($local_xml_path);
}


function set_redis_m2($env_data) {
    //if cache and page_cache already set, then set server host to redis
    if ( array_key_exists('cache', $env_data) ) {
        print_r("Redis is cache already, set updating server name");
        $env_data['cache']['frontend']['default']['backend_options']['server'] = 'redis';
        $env_data['cache']['page_cache']['backend_options']['server'] = 'redis';
    } else {
        print_r("Redis not set, merging env.php array with Redis configuration\n");
        $env_data=array_merge($env_data,return_redis_config());
    }
    return $env_data;
}

function set_redis_session_m2($env_data){
  if ( array_key_exists('session', $env_data) ) {
     print_r("Setting redis ...\n");
     $env_data['session']['save'] = 'redis';
     $env_data['session']['redis'] = array (
    'host' => 'redis-session',
    'port' => '6380',
    'password' => '',
    'timeout' => '2.5',
    'persistent_identifier' => '',
    'database' => '0',
    'compression_threshold' => '2048',
    'compression_library' => 'gzip',
    'log_level' => '1',
    'max_concurrency' => '20',
    'break_after_frontend' => '5',
    'break_after_adminhtml' => '30',
    'first_lifetime' => '600',
    'bot_first_lifetime' => '60',
    'bot_lifetime' => '7200',
    'disable_locking' => '1',
    'min_lifetime' => '60',
    'max_lifetime' => '2592000'
  );
   } else {
       print_r("no sessions data set, check env.php, could be invalid! Exiting....");
       exit(1);
   }
   return $env_data;
}

function set_memcache_m2($env_data) {
    //basically clone of redis method
     if ( array_key_exists('session', $env_data) ) {
        print_r("Setting memcache sessions...\n");
        $env_data['session']['save'] = 'memcache';
        $env_data['session']['save_path'] = 'tcp://memcache:11211';
    } else {
        print_r("no sessions data set, check env.php, could be invalid! Exiting....");
        exit(1);
    }
    return $env_data;
}

function run_command($command) {
    //runs generic shell command and streams
    while (@ ob_end_flush()); // end all output buffers if any

    $proc = popen($command,'r');
    echo '\n';
    while (!feof($proc))
    {
        echo fread($proc, 4096);
        @ flush();
    }
    echo '\n';
}

function rsync($options) {
    //construct command for readability
    $command='rsync -avzP -e "ssh -p ' . $options['ssh_port'] . '" ' . $options['ssh_user'] . '@' . $options['ssh_url'] . ":" . $options['ssh_web_root'] . " " . $options['web_root'] . " --copy-links --exclude=var/* --max-size=100M --delete";
    print_r("Copying with \n" . $command . " use this password: ");
    print_r($ssh_pass);
    while (@ ob_end_flush()); // end all output buffers if any

    $proc = popen($command, 'r');
    echo '\n';
    while (!feof($proc))
    {
        echo fread($proc, 4096);
        @ flush();
    }
    echo '\n';
}

function dump_remote_db($options,$remote_db_info) {
  //db_info assumed to be array extracted from remote host
  //ssh $PROD_SSH_USER@$PROD_SSH_HOST "mysqldump --quick -u$PROD_USER -p$PROD_PASS $PROD_DATABASE" > prod_dump.sql
  //form $command
  $command="ssh -p " . $options['ssh_port'] . ' ' . $options['ssh_user'] . '@' . $options['ssh_url'] . ' "mysqldump --skip-lock-tables --extended-insert=FALSE --verbose -h ' . $remote_db_info['db_host'] .  ' --quick -u' . $remote_db_info['db_user'] . ' -p\'' . str_replace("$", "\\$", $remote_db_info['db_pass']) . '\' ' . $remote_db_info['db'] . '" > /srv/prod_dump.sql';
  print_r("Copying with \n" . $command . " use this password: ");
  print_r($options['ssh_pass']);
  run_command($command);
}

function drop_database_tables($db_host,$db_name,$db_user,$db_pass) {
    //open db connection, list tables, nuke from orbit
    //identical to the bash script we use
    print_r("Dropping database tables...");
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $mysqli->select_db($db_name);
    $mysqli->query('SET foreign_key_checks = 0');
    if ($result = $mysqli->query("SHOW TABLES"))
    {
        while($row = $result->fetch_array(MYSQLI_NUM))
        {
            $mysqli->query('DROP TABLE IF EXISTS '.$row[0]);
        }
    }
    $mysqli->query('SET foreign_key_checks = 1');
    $mysqli->close();
}

function import_database($options,$globals) {
  $command='pv ' . $globals['prod_dump'] . '| mysql -h mysql ' . $globals['db_host']  . ' -u ' . $options['db_user'] . ' -p\'' . $options['db_pass'] . '\' ' . $options['db'];
  print_r($command);
  run_command($command);
}

function update_base_urls($options,$remote_db_info) {
  print_r("Updating default base URLS only...\n");
  $conn = new mysqli('mysql', $options['db_user'], $options['db_pass'], $options['db']);
// Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  $sql = 'update '. $remote_db_info['table_prefix'] . 'core_config_data set value="' . $options['base_url'] . '" where path like "web/%secure/base_url" and scope="default"';
  if ($conn->query($sql) === TRUE) {
      echo "Record updated successfully";
  } else {
      echo "Error updating record: " . $conn->error;
  }

  $conn->close();
}

#update default cookie_domain
function update_cookie_domain($options,$remote_db_info) {
  print_r("Updating cookie default cookie domain only...\n");
  $conn = new mysqli('mysql', $options['db_user'], $options['db_pass'], $options['db']);
// Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  $sql = 'update '. $remote_db_info['table_prefix'] . 'core_config_data set value=".mojostratus.io" where path like "%cookie_domain%" and scope_id=0';
  if ($conn->query($sql) === TRUE) {
      echo "Record updated successfully";
  } else {
      echo "Error updating record: " . $conn->error;
  }

  $conn->close();
}

#update default cookie_domain
function blackhole_m1_tables($options,$remote_db_info) {
    print_r("\nBlackholing a few m1 tables...\n");
    $conn = new mysqli('mysql', $options['db_user'], $options['db_pass'], $options['db']);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $target_tables = ['log_url', 'log_url_info', 'log_visitor', 'log_visitor_info'];
    foreach($target_tables as $table){
        $query = "DELETE FROM ".$remote_db_info['table_prefix'].$table.";";
        $conn->query($query);
        $query = "ALTER TABLE ".$remote_db_info['table_prefix'].$table." ENGINE=BLACKHOLE;";
        if ($conn->query($query) === TRUE) {
            print_r("Successfully blackhole'd table: ".$remote_db_info['table_prefix'].$table."\n");
        } else {
            print_r("Failed to blackhole table '".$remote_db_info['table_prefix'].$table."' - Error: ".$conn->error."\n");
        }
    }

    print_r("\n");
    $conn->close();
}

function deploy_m2($options) {
  echo "php " . $options['web_root'] . "bin/magento maintenance:enable";
  run_command("php " . $options['web_root'] . "bin/magento maintenance:enable");
  run_command("php " . $options['web_root'] . "bin/magento deploy:mode:set production");
  run_command("php " . $options['web_root'] . "bin/magento maintenance:disable");
  run_command("php " . $options['web_root'] . "bin/magento cache:clean");
  run_command("php " . $options['web_root'] . "bin/magento setup:config:set --http-cache-hosts=varnish");
}

function reindex_m1($web_root) {
  run_command("php " . $web_root . "shell/indexer.php --reindexall");
}

function clear_cache_m1(){
  run_command("rm -rf " . $web_root . "var/cache");
  run_command("redis-cli -h redis flushall");
}
//MAIN LOOP
//drop database
drop_database_tables($globals['mysql_host'],$options['db'],$options['db_user'],$options['db_pass']);
//make sure target web root is clear first
//now sync files over
echo "Starting rsync...";
rsync($options);
//get old db info for remote var_dump

if ($options['magento']=="m2") {
  $remote_db_info=get_remote_db_info_m2($options['web_root'] . "app/etc/env.php");
  //dump database from remote host
  dump_remote_db($options,$remote_db_info);
  //load env.php
  $env_data = load_env_m2($options['web_root'] . "app/etc/env.php");
  //set redis configuration
  $env_data = set_redis_m2($env_data);
  //set memcache sessions for Stratus
  $env_data = set_redis_session_m2($env_data);
  //set db creds
  $env_data=set_db_creds_m2($env_data,$options);
  //write to file
  $output = var_export($env_data, true);
          try {
              $fp = fopen($options['web_root'] . "app/etc/env.php", 'w');
              fwrite($fp, "<?php\n return " . $output . ";\n");
              fclose($fp);
          } catch (\Exception $e) {
              throw new \Exception("Could not write file" . $e);
              exit(1);
          }
  //remove definers
  run_command("sed -i 's/DEFINER=[^*]*\*/\*/g' /srv/prod_dump.sql");
  //files copy , database moved, lets import something
  import_database($options,$globals);

  update_base_urls($options,$remote_db_info);
  update_cookie_domain($options,$remote_db_info);
  deploy_m2($options);
  }


  if ($options['magento']=="m1") {
    $remote_db_info=get_remote_db_info_m1($options['web_root'] . "app/etc/local.xml");
    //dump database from remote host
    dump_remote_db($options,$remote_db_info);
    update_local_xml_m1($options,$options['web_root'] . "app/etc/local.xml");
    //remove definers
    run_command("sed -i 's/DEFINER=[^*]*\*/\*/g' /srv/prod_dump.sql");
    import_database($options,$globals);
    update_base_urls($options,$remote_db_info);
    reindex_m1($options['web_root']); //needs made
    update_cookie_domain($options,$remote_db_info);
    blackhole_m1_tables($options,$remote_db_info);
    clear_cache_m1($options['web_root']); //needs made
    echo "migration complete, in theory";
  }


?>
