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
     // Required value
);
$options = getopt($shortopts,$longopts);
print_r($options);
//functionssaaaaa

function return_redis_config() {
//array for PHP config
    return array (
     'cache' =>
      array (
        'frontend' =>
        array (
          'default' => 
          array (
            'backend' => 'Cm_Cache_Backend_Redis',
            'backend_options' =>
            array (
              'server' => 'redis',
              'database' => '0',
              'port' => '6379',
            ),
          ),
          'page_cache' =>
          array (
            'backend' => 'Cm_Cache_Backend_Redis',
            'backend_options' =>
            array (
              'server' => 'redis',
              'port' => '6379',
              'database' => '1',
              'compress_data' => '0',
            ),
          ),
        ),
      )
    );
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
    $db_info  = array(
    'db'=> $env_data['db']['connection']['default']['dbname'],
    'db_user'=> $env_data['db']['connection']['default']['username'],
    'db_pass' => $env_data['db']['connection']['default']['password'],
    'db_host' => $env_data['db']['connection']['default']['host'],
    );
    return $db_info;
}

function set_db_creds_m2($env_data,$options) {
          $env_data['db']['connection']['default']['dbname']=$options['db'];
          $env_data['db']['connection']['default']['username']=$options['db_user'];
          $env_data['db']['connection']['default']['password']=$options['db_pass']; 
          $env_data['db']['connection']['default']['host']='mysql';
          return $env_data;
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

function rsync($ssh_user,$ssh_url,$ssh_port,$ssh_web_root,$target) {
    //construct command for readability
    
    $command='rsync -avz -e "ssh -p ' . $ssh_port . '" ' . $ssh_user . '@' . $ssh_url . ":" . $ssh_web_root . " " . $target;
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

function dump_remote_db($options,$db_info) {
  //db_info assumed to be array extracted from remote host
  //ssh $PROD_SSH_USER@$PROD_SSH_HOST "mysqldump --quick -u$PROD_USER -p$PROD_PASS $PROD_DATABASE" > prod_dump.sql
  //form $command
  $command="ssh -p " . $options['ssh_port'] . ' ' . $options['ssh_user'] . '@' . $options['ssh_url'] . ' "mysqldump --quick -u' . $db_info['db_user'] . ' -p' . $db_info['db_pass'] . ' ' . $db_info['db'] . '" > /srv/prod_dump.sql';
  print_r("Copying with \n" . $command . " use this password: ");
  print_r($options['ssh_pass']);
  run_command($command);
}

function drop_database_tables($db_host,$db_name,$db_user,$db_pass) {
    //open db connection, list tables, nuke from orbit
    //identical to the bash script we use
    print_r("Dropping database tables...");
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
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
  $command='pv ' . $globals['prod_dump'] . '| mysql -h ' . $globals['db_host']  . ' -u ' . $options['db_user'] . '-p' . $options['db_pass'] . ' ' . $options['db'];
  run_command($command);
}


//MAIN LOOP
//drop database
drop_database_tables($globals['mysql_host'],$options['db_name'],$options['db_user'],$options['db_pass']);
//make sure target web root is clear first
//run_command("rm -rf " . $options['web_root']);
//now sync files over
rsync($options['ssh_user'],$options['ssh_url'],$options['ssh_port'],$options['ssh_web_root'],$options['web_root']);
//get old db info for remote var_dump
$db_info=get_remote_db_info_m2($options['web_root'] . "app/etc/env.php");
//dump database from remote host
dump_remote_db($options,$db_info);
//load env.php
$env_data = load_env_m2($options['web_root'] . "app/etc/env.php");
//set redis configuration
$env_data = set_redis_m2($env_data);
//set memcache sessions for Stratus
$env_data = set_memcache_m2($env_data);
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
//files copy , database moved, lets import something
import_database($options,$globals);


?>