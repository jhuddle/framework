<?php

namespace Framework;

class Resource {

	public string $folder;
	public string $suffix;

	public function __construct(
		string $folder = '.',
		string $suffix = '.php'
	)
	{
		$this->folder = realpath($folder);
		$this->suffix = $suffix;
	}

	public function __invoke(
		string $file,
		array $vars = []
	): object
	{
		if (!str_ends_with($file, $this->suffix)) {
			$file = $this->folder .'/'. $file . $this->suffix;
		}

		return new class ($file, $vars) {

			private string $file;
			private array $vars;

			public function __construct(
				string $file,
				array $vars
			)
			{
				$this->file = $file;
				$this->vars = $vars;
			}

			public function use(
				array $vars = []
			): self
			{
				return new self(
					$this->file,
					array_merge($this->vars, $vars)
				);
			}

			public function __invoke(
				...$args
			): mixed
			{
				extract($this->vars);
				extract($args);
				return include $this->file;
			}

			public function __toString(
			): string
			{
				ob_start();
				$this();
				return ob_get_clean();
			}

		};
	}

}
