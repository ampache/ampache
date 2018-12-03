<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use PDO;
use PDOException;
use Illuminate\Console\ConfirmableTrait;
use Dotenv\Dotenv;
use Dotenv\Loader;

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
    
    protected $dbname = "";
    
    protected $password = "";
    
    protected $dsn = "";
    
    protected $user = "";
    
    protected $port = "";
    
    protected $host = "";
    
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
        chdir(base_path());
        $exampleFile = base_path('.env.example');
        $envFile     = base_path('.env');
        $copied      = false;
        if (file_exists($envFile)) {
            $resp =  $this->ask('Do you want to overwrite existing .env file?', 'y|N');
            if (strtolower($resp) !== 'n') {
                $copied = $this->copyFile($exampleFile, $envFile);
            }
        } else {
            $copied = $this->copyFile($exampleFile, $envFile);
        }
        //wait for system to finish if copied.
        if ($copied) {
            sleep(3);
        }
 
        $dotenv = new Dotenv(base_path(), '.env');
        $dotenv->overload();
        $this->host   = env('DB_HOST', 'localhost');
        $this->dbname = env('DB_DATABASE', 'ampache');
        $this->port   = env('DB_PORT', '3306');
        $this->user   = env('DB_USERNAME', 'root');

        $this->info('Generating new application key');
        
        $this->call("key:generate");
        
        
        $connectionOk = false;
        while ($connectionOk == false) {
            $this->dbname = $this->ask('Please enter the mysql database name:', $this->dbname);
            $this->setKeyInEnvironmentFile([env('DB_DATABASE'), 'DB_DATABASE', $this->dbname]);
            $this->host = $this->ask('Please enter the mysql database host:', $this->host);
            $this->setKeyInEnvironmentFile([env('DB_HOST'), 'DB_HOST', $this->host]);
            $this->port = $this->ask('Please enter the mysql database port:', $this->port);
            $this->setKeyInEnvironmentFile([env('DB_PORT'), 'DB_PORT', $this->port]);
            $this->user = $this->ask('Please enter the mysql database user name: ', $this->user);
            $this->setKeyInEnvironmentFile([env('DB_USERNAME'), 'DB_USERNAME', $this->user]);
            do {
                $this->password  = $this->secret('Please enter the password associated with this user');
                $pass1           = $this->secret('Please confirm password: ');
                if ($this->password != $pass1) {
                    $this->error("The passwords don't match. Please enter again.");
                    $this->password = $pass1 = false;
                }
            } while (empty($this->password) || empty($pass1));
            $this->setKeyInEnvironmentFile([env('DB_PASSWORD'), 'DB_PASSWORD', $this->password]);
            config(['database.connections.mysql.password' => $this->password]);
            //test connection.
            $this->dsn = 'mysql:host=' . $this->host . ";port=" . $this->port;
            try {
                $this->dbh    = new \PDO($this->dsn, $this->user, $this->password);
                $connectionOk = true;
            } catch (\PDOException $e) {
                $this->error("There is an error in hostname, username or password. Please enter again.");
                continue;
            }
        }
        //Check for existing database
        $response = '';
        if ($this->schemaExists($this->dbname)) {
            $response = $this->choice('Database ' . $this->dbname . ' exists. Do you want to overwrite?', ['overwrite', 'change', 'cancel'], 0);
            if ($response == 'overwrite') {
                $this->dropDatabase($this->dbname);
            } elseif ($response == 'change') {
                $this->dbname = $this->ask('Please enter the new database name:');
                $this->setKeyInEnvironmentFile([env('DB_DATABASE'), 'DB_DATABASE', $this->dbname]);
            } else {
                exit("Install cancelled\n");
            }
        }
        
        $this->createSchema($this->dbname);
        //Migrate database structure
        $loader = new Loader(base_path(), '.env');
        $dotenv->overload();
        
        passthru('php artisan migrate --force');
        passthru('php artisan db:seed');
        $user_pass = bcrypt('guest');
        try {
            $created_at = now();
            $statement  = "SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'";
            $this->dbh->exec($statement);
            $statement = "INSERT INTO `ampache`.`users` (`id`, `username`, `fullname`, `access`, `email`, `password`, `created_at`) " .
                "VALUES (0, 'guest', 'Ampache Guest', 5, 'guest@ampache.org', '" . $user_pass . "', '" . $created_at . "');";
            $this->dbh->exec($statement);
        } catch (PDOException $e) {
            $this->error($e->getMessage());
            exit;
        }
        //create new app key.
      
        $this->setKeyInEnvironmentFile([env('AMPACHE_INSTALLED'), 'AMPACHE_INSTALLED', 'YES']);
        
        $this->comment("\nFinished initializing Ampache.");
    }
    
    protected function copyFile($exampleFile, $envFile)
    {
        $inFile  = fopen($exampleFile, 'r');
        $outFile = fopen($envFile, 'wb');
        try {
            $contents = fread($inFile, filesize($exampleFile));
            fwrite($outFile, $contents) ;
            fclose($inFile);
            fclose($outFile);
        } catch (Exception $e) {
            return false;
        }
    }
    
    protected function setKeyInEnvironmentFile($key)
    {
        $this->writeNewEnvironmentFileWith($key);
        
        return true;
    }
    
    protected function writeNewEnvironmentFileWith($key)
    {
        $infile  = file_get_contents($this->laravel->environmentFilePath());
        $pattern = $this->keyReplacementPattern($key);
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $pattern,
            $key[1] . '=' . $key[2],
            $infile
            ));
    }
    
    protected function dropDatabase($dbname)
    {
        try {
            $this->dsn    = 'mysql:host=' . $this->host . ";port=" . $this->port;
            $this->dbh    = new PDO($this->dsn, $this->user, $this->password);
            
            $query = "DROP DATABASE " . $dbname;
            $this->dbh->query($query);
        } catch (\PDOException $e) {
            $this->error("There was an error creating new database");
            exit;
        }
    }
    
    protected function keyReplacementPattern($key)
    {
        $escaped = preg_quote('=' . $key[0], '/');
        
        return "/^" . $key[1] . "{$escaped}/m";
    }
    
    protected function schemaExists($dbname)
    {
        try {
            $this->dbh    = new PDO($this->dsn, $this->user, $this->password);
            
            $sql   =  "SELECT COUNT(`schema_name`) FROM `information_schema`.`schemata` WHERE `schema_name` = '$dbname'";
            $count = $this->dbh->query($sql)->fetchColumn();

            return $count > 0 ? true : false;
        } catch (PDOException $e) {
            $this->error("There was an error accessing the information table!");
            exit;
        }
    }
    
    protected function createSchema($dbname)
    {
        try {
            $this->dbh = new PDO($this->dsn, $this->user, $this->password);
            $query     = "CREATE DATABASE $dbname CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';";
            $this->dbh->query($query);
        } catch (\PDOException $e) {
            $this->error("There was an error creating new database");
            exit;
        }
    }
}
