<?php

namespace Framework;

class Route {

	public string|null $prefix = null;

	public function prefix(
		string $prefix = ""
	): self
	{
		$new = new self();
		$new->prefix = $this->prefix .'/'. trim($prefix, '/');
		return $new;
	}

	public function __invoke(
		string $route,
		callable $func
	): self
	{
		if (!isset($_SERVER['REQUEST_URI'])) {
			return $this;
		}
		$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		$vars = [];

		if (!str_starts_with($route, '/')) {
			$route = '/'. $route;
		}
		$route_pattern = preg_replace_callback(
			'~/\\\\<(\w+)(?:\\\\:)?(?:\\\\(\?))?(.*?)\\\\>(?=/|$)~',
			function ($matches) use (&$vars)
			{
				$vars[] = $matches[1];
				$is_optional = $matches[2];  // i.e. "?" (optional) | "" (not optional)
				$pattern = $matches[3];

				switch (strtolower($pattern))
				{
					case 'int':
					case 'integer':
						$pattern = '-?\d+';
						break;

					case 'float':
					case 'double':
						$pattern = '-?\d*\.?\d+(?:[Ee]-?\d+)?';
						break;

					case 'bool':
					case 'boolean':
						$pattern = '[Tt][Rr][Uu][Ee]|[Ff][Aa][Ll][Ss][Ee]';
						break;

					case 'binary':
						$pattern = '(?:[\w+=-]|%2[BbFf]|%3[Dd])+';
						break;

					case 'string':
						$pattern = '[^/<>]+';
						break;

					default:
						$pattern = '[\w\~.-]+';
						break;
				}

				return '(?:/('. $pattern .'))'. $is_optional;
			},
			preg_quote($this->prefix . $route, '~')
		);
		$route_regex = '~^'. $route_pattern .'$~';

		if (preg_match($route_regex, $url_path, $values, PREG_UNMATCHED_AS_NULL)) {
			array_shift($values);  // i.e. remove $values[0] (whole pattern)
			$args = array_filter(
				array_combine($vars, $values),
				fn ($value) => $value !== null
			);
			$next = $func(...$args);
			if ($next !== false) exit;
		}

		return $this;
	}

	public function __call(
		string $name,
		array $args
	): self
	{
		if (!strcasecmp($name, @$_SERVER['REQUEST_METHOD'])) {
			$this(...$args);
		}
		return $this;
	}

}
