# Altvel Consoler

Built Specifically for Altvel and can only be ported with a lot of code change. It is a closed code structure.


### Console Commands

	=> Console Command


### Installation
##
	=> This will already be pre-installed with Altvel Framework

```bash
composer require sevens/consoler
```


### Initializing the SchemaMap Engine
##
	=> Since You're most likely using Altvel Framework Engineer Console, You won't be setting this up.
```php

$schemaMap = new SchemaMap($config = [ 
	'directory' => __DIR__.'/migration', 
	'migrator'  => 'Migration.php', 
	'populator' => 'Population.php'
]);

```


### Migrator file
##
	=> id is automatically generated on all tables by the schemaMap Engine

```php
return[
	'users' => [
		'name' => $this->string($max_length=125, $null=false),
		'email' => $this->string($max_length=125, $null=false, $key='unique'),
		'password' => $this->string($max_length=125),
		'backup_pass' => $this->string($max_length=150),
		'activation' => $this->string($max_length=225),
		'verified' => $this->oneOf($options=["'true'", "'false'"], $default="'false'" ),
		'created_at' => $this->dateTime(),
		'deleted' => $this->oneOf($options=["'true'", "'false'"], $default="'false'" )
	],
	'contact_us' => [
		'name' => $this->string($max_length=125, $null=false),
		'email' => $this->string($max_length=125),
		'feedback' => $this->string($max_length=1025),
		'created_at' => $this->datetime(),
		'deleted' => $this->oneOf($options=["'true'", "'false'"], $default="'false'" )
	],
	'user_sessions' => [
		'user_id' => $this->foreign_key($table='users', $column='id', $type = 'int' ),
		'session' => $this->string($max_length=225, $null=false),
		'user_agent' => $this->string($max_length=225, $null=false),
		'push_token' => $this->string($max_length=225, $null=false),
		'created_at' => $this->dateTime(),
		'deleted' => $this->oneOf($options=["'true'", "'false'"], $default="'false'" )
	],
];
```

### Usage: Populator file
##

***Populate a table with data by adding array of arrays to the population.php return array in this format***

```php
return [
	'table name' => [ 'column name' => 'value' , 'column name' => 'value' , 'column name' => 'value' ],
	'table name' => [...],
];
```

***Example Use Case***
```php

return [

	'users' => [
		[
			'name' => "Elisha TemmyScope",
			'email' => "esdentemmy@gmail.com",
			'password' => hash("password"),
			'activation' => "random code",
			'verified' => "false",
			'created_at' => date("Y-m-d h:i:s"),
			'deleted' => "false"
		]
	],

];

```