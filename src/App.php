<?php

namespace Framework;

use Directory;

class App {

	public string|null $input = null;

	public function __construct(
		array $resources = [],
		bool $session = true
	)
	{
		if ($session && session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->input = file_get_contents('php://input');

		$resources += [
			'session'  => new Session(),
			'route'    => new Route(),
			'request'  => new Request(),
			'db'       => getenv('DBMS')
			                ? new Database(
			                  	getenv('DBMS'),
			                  	getenv('DB_HOST'),
			                  	getenv('DB_PORT'),
			                  	getenv('DB_NAME'),
			                  	getenv('DB_USER'),
			                  	getenv('DB_PASS')
			                  )
			                : null
		];

		$this->use($resources);
	}

	public function use(
		array $resources = []
	): self
	{
		foreach ($resources as $name => $item) {
			if ($item instanceof Directory) {
				$this->$name = new Resource($item->path);
			} else {
				$this->$name = $item;
			}
		}
		return $this;
	}

	public function __call(
		string $name,
		array $args
	): mixed
	{
		return ($this->$name)(...$args);
	}

}
