<?php
namespace Seven\Migration;

use \PDO;
use \PDOException;

class SchemaMap{

	public function __construct($config = [
		'directory' => __DIR__,
		'migrator'  => 'Migration.php',
		'populator' => 'Population.php'
	]){
		$this->directory = $config['directory'];
		$this->migrator = @include(
			$this->directory.DIRECTORY_SEPARATOR.$config['migrator']
		) 
		?? 
		exit("Something is Wrong:\n
			1. Check that you passed a valid configuration parameter to the __construct.\n
			2. Ensure that your file is a valid php file returning an array E.g. <?php \nreturn [\n];
		");
		$this->time = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
	}

	public function db($value)
	{
		try {
			$user = $_ENV['DB_USER'];
			$password = $_ENV['DB_PASS'];
			$server = $_ENV['DB_HOST'];
			$db = str_replace("pdo_", '', $_ENV['DB_DRIVER']);
			$collate = $_ENV["COLLATE"] ?? "utf8mb4_unicode_ci";
    	$conn = new PDO("$db:host=$server;", $user, $password);
    	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    	$conn->exec("CREATE DATABASE {$value} COLLATE $col;");
    	$this->log("DATABASE: \n\t[ NAME => $value, MODE => CREATE, CREATED_AT => {$time} ]\n");
    }catch(PDOException $e){
	    echo $e->getMessage();
    }
	}

	public static function init($config)
	{
		return new self($config);
	}

	protected function log(string $message)
	{
		$fileHandle = fopen($this->directory.'/'.'Migrations.History', 'a+');
		fwrite($fileHandle, $message);
		echo "Schema Transaction Completed Successfully. \nView Transaction Log in Migration.History\n", "\n";
	}	

	public static function run()
	{	
		try {
			$dbname = $_ENV[ 'DB_NAME' ];
			$user = $_ENV[ 'DB_USER' ];
			$password = $_ENV[ 'DB_PASS' ];
			$server = $_ENV[ 'DB_HOST' ];
			$db = str_replace("pdo_", '', $_ENV[ 'DB_DRIVER' ]);
    	$conn = new PDO("$db:host=$server;dbname=$dbname", $user, $password);
    	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    	$scheme = new MigrationEngine;
    	$this->parserIfExists($scheme, $conn);
    	$this->InsertIfExists($scheme);
    }catch(PDOException $e){
	    echo $e->getMessage();
    }
	}

	public function migrate()
	{
		try {
			$dbname = $_ENV['DB_NAME'];
			$user = $_ENV['DB_USER'];
			$password = $_ENV['DB_PASS'];
			$server = $_ENV['DB_HOST'];
			$db = str_replace("pdo_", '', $_ENV['DB_DRIVER']);
    	$conn = new PDO("$db:host=$server;dbname=$dbname", $user, $password);
    	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    	$this->parserIfExists($this, $conn);
    }catch(PDOException $e){
	    echo $e->getMessage();
    }
	}

	public function populate()
	{
		try {
			$dbname = $_ENV[ 'DB_NAME' ];
			$user = $_ENV[ 'DB_USER' ];
			$password = $_ENV[ 'DB_PASS' ];
			$server = $_ENV[ 'DB_HOST' ];
			$db = str_replace("pdo_", '', $_ENV['DB_DRIVER']);
    	$conn = new PDO("$db:host=$server;dbname=$dbname", $user, $password);
    	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    	$this->insertIfExists($this, $conn);
    }catch(PDOException $e){
	    echo $e->getMessage();
    }
	}

	protected static function queryMe($sql, $conn)
	{
		try {
			$conn->exec($sql);
	    }catch(PDOException $e){
		    echo $e->getMessage();
	    }
	}

	protected static function insertIfExists($scheme)
	{
		$data = $scheme->populate();
		if (!empty($data)) {
			foreach ($data as $table => $entry) {
				Model::setTable($table)->insert($entry);
				echo "Data has been inserted into ", $table, " table.";
			}
		}
	}

	protected function parserIfExists($scheme, $conn)
	{
		if ( !is_array($this->migrator) || empty($this->migrator)) {
			echo "Migration.php must return Array & can not be empty";
			return;
		}
		foreach ($this->migrator as $table => $columns) {
			$queue = ["ALTER TABLE :table ADD PRIMARY KEY (id);", "ALTER TABLE :table  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;"];
			$_sql = "CREATE TABLE `{$table}` ( id int(11) NOT NULL, ";
			foreach($columns as $key => $value){
				$_sql .= str_replace(":column", $key, $value[0]);
				if($value[1] !== ''){
					$queue[] = str_replace(":column", $key, str_replace(":table", $table, $value[1]) );
				}
			}
			self::queryMe(rtrim($_sql, ', ').");", $conn);
			foreach ($queue as $key => $value) {
				self::queryMe(str_replace(':table', $table, $value), $conn);
			}
			$columns = array_keys($columns);
			$this->log(
				"TABLE: \n\t[ NAME => $table, COLUMNS => $columns, MODE => MIGRATED, CREATED_AT => {$this->time} ]\n"
			);
			
		}
	}

	public function integer($max_length=10)
	{
		return [":column int({$max_length}) NOT NULL, ", ""];
	}
	public function double($max_length=10)
	{
		return [ ":column double, ", ""];
	}
	public function float($max_length=10)
	{
		return [ ":column float({$max_length}), ", ""];
	}
	public function string($max_length, $null = false, $key='primary')
	{
		$null = ($null === false) ? "NOT NULL" : "NULL";
		$t = "";
		if(!empty($key)){
			$key = strtolower($key);
			if( $key === 'unique' ){
				$t = "ALTER TABLE :table ADD UNIQUE :column (:column);";
			}elseif( $key === 'fulltext' ){
				$t = "ALTER TABLE :table ADD FULLTEXT KEY :column (:column);";
			} elseif( $key === 'index' ){
				$t = "CREATE INDEX :column ON :table (:column);";
			}
		}
		if ($max_length > 63000) {
			$type =  "text";
		}elseif ($max_length <= 63000) {
			$type = "varchar({$max_length})";
		}elseif ($max_length < 18) {
			$type =  "char({$max_length})" ;
		}
		return [ ":column {$type} {$null}, ", $t ];
	}
	public function oneOf(array $options, $default)
	{
		$options  = implode(', ', $options);
		return [ ":column enum({$options}) NOT NULL DEFAULT {$default}, ", ""];
	}
	public function datetime()
	{
		return [ ":column DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ", ""];
	}
	public function foreign_key($table, $column, $type=null, $length=10)
	{
		$type  = (strtolower($type) === 'string') ? "varchar({$length})" : 'int ';
		return [":column {$type} NOT NULL,", "ALTER TABLE :table ADD FOREIGN KEY (:column) REFERENCES {$table}({$column});"];
	}
}