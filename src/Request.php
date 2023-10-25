<?php

namespace Framework;

class Request {

	public static function http(
		string $url,
		string $method = 'GET',
		array|null $headers = null,
		string|array|null $content = null
	): object
	{
		if ($headers === null) $headers = [];

		$context = stream_context_create([
			'http' => [
				'method' => strtoupper($method),
				'header' => array_map(
					fn ($name, $value) => trim($name) .': '. trim($value),
					array_keys($headers),
					array_values($headers),
				),
				'content' => is_array($content) ? http_build_query($content) : $content,
				'ignore_errors' => true,
			]
		]);

		$request = fopen($url, 'r', false, $context);
		$content = $request ? stream_get_contents($request) : null;
		if ($request) fclose($request);

		$headers = [];
		foreach ($http_response_header ?? [] as $header) {
			$header = explode(':', $header, 2);
			if (isset($header[1])) {
				$headers[trim($header[0])] = trim($header[1]);
			} else {
				$headers[] = trim($header[0]);
			}
		}

		return new class ($headers, $content) {

			public array $headers;
			public string|null $content;

			public function __construct(
				array $headers,
				string|null $content
			)
			{
				$this->headers = $headers;
				$this->content = $content;
			}

			public function __toString(
			): string
			{
				return $this->content ?? "";
			}

		};
	}

	public static function __callStatic(
		string $name,
		array $args
	): object
	{
		array_splice($args, 1, 0, $name);
		return self::http(...$args);
	}

	public function __call(
		string $name,
		array $args
	): object
	{
		return self::__callStatic($name, $args);
	}

}
