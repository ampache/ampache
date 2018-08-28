<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use PDO;
use PDOException;

class InitAmpache extends Command
{
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
        $name = $this->ask('Please enter the mysql database name?');
        $user = $this->ask('Please enter the mysql database user name?');
        $pass = $this->secret('Please enter the password associated with this user');
        $user_pass = bcrypt('guest');
        //CREATE DATABASE menagerie;$dsn = 'mysql:dbname=testdb;host=127.0.0.1';
        $dsn = "mysql:host=127.0.0.1";
        try {
            $created_at = now();
            $dbh       = new PDO($dsn, $user, $pass);
            $statement = "CREATE DATABASE " . $name . ";";
            $dbh->exec($statement);
            $statement = "SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'";
            $dbh->exec($statement);
            $statement = "INSERT INTO `ampache`.`users` (`id`, `username`, `fullname`, `access`, `email`, `password`) " .
            "VALUES (0, 'guest', 'Ampache Guest', 5, 'guest@ampache.org', '" . $user_pass . "');";
            $dbh->exec($statement);
        } catch (PDOException $e) {
            $this->error($e->getMessage());
            exit;
        }
    }
}
