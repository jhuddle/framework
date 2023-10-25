<?php

namespace Framework;

use PDO;

class Database {

	public string $dbms;
	public PDO $connection;

	public function __construct(
		string $dbms,
		string $host,
		string $port = "",
		string $name = "",
		string $user = "",
		string $pass = ""
	)
	{
		$dbms = strtolower($dbms);
		switch ($dbms)
		{
			case 'sqlite':
				$this->dbms = 'sqlite';
				$this->connection = new PDO(
					$this->dbms .":". realpath($host)
				);
				break;

			case 'mysql':
			case 'mariadb':
				$this->dbms = 'mysql';
				$this->connection = new PDO(
					$this->dbms .":host=$host:$port;dbname=$name", $user, $pass
				);
				break;

			case 'pgsql':
			case 'psql':
			case 'postgres':
			case 'postgresql':
				$this->dbms = 'pgsql';
				$this->connection = new PDO(
					$this->dbms .":host=$host;port=$port;dbname=$name;user=$user;password=$pass"
				);
				break;

			case 'sqlsrv':
			case 'sqlserver':
			case 'mssqlsrv':
			case 'mssqlserver':
			case 'azure':
				$this->dbms = 'sqlsrv';
				$this->connection = new PDO(
					$this->dbms .":Server=$host,$port;Database=$name", $user, $pass
				);
				break;

			case 'odbc':
			case 'db2':
			case 'ibm':
				$this->dbms = $dbms === 'ibm' ? 'ibm' : 'odbc';
				$this->connection = new PDO(
					$this->dbms .":DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME=$host;PORT=$port;DATABASE=$name;PROTOCOL=TCPIP;UID=$user;PWD=$pass"
				);
				break;

			case 'oci':
			case 'oracle':
				$this->dbms = 'oci';
				$this->connection = new PDO(
					$this->dbms .":dbname=//$host:$port/$name"
				);
				break;

			case 'firebird':
				$this->dbms = 'firebird';
				$this->connection = new PDO(
					$this->dbms .":dbname=$host/$port:$name"
				);
				break;
		}
	}

	public function execute(
		string $query,
		array $params = [],
		bool $assoc = false
	): array|object
	{
		$stmt = $this->connection->prepare($query);
		$stmt->execute($params);
		return $stmt->fetchAll($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
	}

	public function executeMany(
		string $query,
		array $params = [],
		bool $assoc = false
	): array
	{
		$result = [];
		$stmt = $this->connection->prepare($query);
		for ($i = 0; $i < count($params); $i++) {
			$stmt->execute($params[$i]);
			$result[] = $stmt->fetchAll($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
		}
		return $result;
	}

	public function transaction(
		callable $func
	): mixed
	{
		try {
			$this->connection->beginTransaction();
			$result = $func($this);
			$this->connection->commit();
			return $result;
		}
		catch (\Throwable $error) {
			$this->connection->rollBack();
			throw $error;
		}
	}

}
