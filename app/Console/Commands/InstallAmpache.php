<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use PDOException;
use Illuminate\Console\ConfirmableTrait;


class InstallAmpache extends Command
{
   use ConfirmableTrait;
   
   protected $dbh = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'ampache:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Ampache';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         //SET [GLOBAL|SESSION] sql_mode='NO_AUTO_VALUE_ON_ZERO'
        //ALTER TABLE users AUTO_INCREMENT = 0;
        $exampleFile = base_path('.env.example');
        $envfile     = base_path('.env');
        if (file_exists($envfile)) {
            $resp =  $this->ask('Do you want to overwrite existing .env file?', 'no');
            if (strtolower($resp) !== 'no') {
                copy($exampleFile, $envfile);
            }
        }
        //create new app key.
        $this->callSilent('key:generate');
        $connectionOk = false;
        While ($connectionOk == false) {
            $dbname = $this->ask('Please enter the mysql database name:', env('DB_DATABASE', 'ampache'));
            config(['database.connections.mysql.database' => $dbname]);
            $this->setKeyInEnvironmentFile([env('DB_DATABASE'), 'DB_DATABASE', $dbname]);
            $host = $this->ask('Please enter the mysql database host:', env('DB_HOST', 'localhost'));
            config(['database.connections.mysql.host' => $host]);
            $this->setKeyInEnvironmentFile([env('DB_HOST'), 'DB_HOST', $host]);
            $port = $this->ask('Please enter the mysql database port:', env('DB_PORT', '3306'));
            config(['database.connections.mysql.port' => $port]);
            $this->setKeyInEnvironmentFile([env('DB_PORT'), 'DB_PORT', $port]);
        
            $user = $this->ask('Please enter the mysql database user name: ', env('DB_USERNAME'));
            config(['database.connections.mysql.username' => $user]);
            $this->setKeyInEnvironmentFile([env('DB_USERNAME'), 'DB_USERNAME', $user]);
            do {
                $pass  = $this->secret('Please enter the password associated with this user');
                $pass1 = $this->secret('Please confirm password: ');
                if ($pass != $pass1) {
                $this->error("The passwords don't match. Please enter again.");
                }
            } while ($pass != $pass1);
            config(['database.connections.mysql.password' => $pass]);
            $this->setKeyInEnvironmentFile([env('DB_PASSWORD'), 'DB_PASSWORD', $pass]);
            //test connection.
            $dsn = 'mysql:host=' . $host;
            try {
                $this->dbh = new \PDO($dsn, $user, $pass);
                $connectionOk = true;
            } catch (\PDOException $e) {
                $this->error("There is an error in hostname, username or password. Please enter again.");
            }
        }
        //Check for existing database
        while ($this->schemaExists($dbname))
         {
            $response = $this->ask('Database ' . $dbname . ' exists. Do you want to overwrite?', 'y/N');
            if (substr($response, 0,1) === 'y') {
                $this->dropDatabase($dbname);
                break;
             } else {
                $dbname = $this->ask('Please enter the mysql database name:', env('DB_DATABASE', 'ampache'));
            }  
        }
        $this->createSchema($dbname);
        
        //Migrate database structure
        $this->call('migrate');
        $this->call('db:seed');
        $user_pass = bcrypt('guest');
        $dsn       = "mysql:host=" . env('DB_HOST', "localhost");
        try {
            $created_at = now();
            $statement  = "SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'";
            $this->dbh->exec($statement);
            $statement = "INSERT INTO `ampache`.`users` (`id`, `username`, `fullname`, `access`, `email`, `password`, `created_at`) " .
                "VALUES (0, 'guest', 'Ampache Guest', 5, 'guest@ampache.org', '" . $user_pass . "', '" . $created_at . "');";
            $res = $this->dbh->exec($statement);
        } catch (PDOException $e) {
            $this->error($e->getMessage());
            exit;
        }
        
        $this->setKeyInEnvironmentFile([env('AMPACHE_INSTALLED'), 'AMPACHE_INSTALLED', 'YES']);
        
        $this->comment("\nFinished initializing Ampache.");
    }

   protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $key[0];
        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }
        
        $this->writeNewEnvironmentFileWith($key);
        
        return true;
    }
    
    protected function writeNewEnvironmentFileWith($key)
    {
        $infile = file_get_contents($this->laravel->environmentFilePath());
        $pattern = $this->keyReplacementPattern($key);
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $pattern,
            $key[1] . '=' . $key[2],
            $infile
            ));
    }
    
    protected function dropDatabase($dbname)
    {
        $query = "DROP DATABASE " . $dbname;
        return DB::statement($query);
    }
    
    protected function keyReplacementPattern($key)
    {
        $escaped = preg_quote('=' . $key[0], '/');
        
        return "/^" . $key[1] . "{$escaped}/m";
    }
    
    protected function schemaExists($dbName)
    {
        try {
           $query =  "SELECT schema_name FROM information_schema.schemata WHERE schema_name = '$dbName'";
           $result = $this->dbh->query($query);
           return $result->rowCount() >0 ? true : false;
        } catch (PDOException $e) {
            $this->error("There was an error accessing the information table!");
            exit;
        }
    }
    
    protected function createSchema($dbName)
    {
        try {
            $charset = config(['database.connections.mysql.charset']);
            $collation = config(['database.connections.mysql.collation']);
            $query = "CREATE DATABASE ampache CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';";
            $result = $this->dbh->query($query);
            
        } catch (\PDOException $e) {
            $this->error("There was an error creating new database");
            exit;
        }
    }

}