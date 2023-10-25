<?php

namespace Framework;

class Session {

	public static function isActive(
	): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}

	public static function start(
		array $options = []
	): void
	{
		if (!self::isActive()) {
			session_start($options);
		}
	}

	public static function refresh(
	): void
	{
		session_regenerate_id();
	}

	public static function set(
		string|int $key,
		mixed $value
	): void
	{
		$_SESSION[$key] = $value;
	}

	public static function get(
		string|int $key
	): mixed
	{
		return $_SESSION[$key];
	}

	public static function exists(
		string|int $key
	): bool
	{
		return isset($_SESSION[$key]);
	}

	public static function unset(
		string|int $key
	): void
	{
		unset($_SESSION[$key]);
	}

	public static function clear(
	): void
	{
		$_SESSION = [];
	}

	public static function destroy(
	): void
	{
		self::clear();
		if (self::isActive()) {
			self::refresh();
			session_destroy();
		}
	}

	public static function reset(
		array $options = []
	): void
	{
		self::destroy();
		self::start($options);
	}

}
