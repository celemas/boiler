<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\UnexpectedValueException;

/** @api */
final class Escapers implements Contract\RegistersEscapers
{
	private const string HTML = 'html';

	public readonly string $default;

	/** @var array<non-empty-string, Contract\Escaper> */
	private array $registry;

	/** @param array<non-empty-string, Contract\Escaper> $escapers */
	public function __construct(
		array $escapers = [],
		string $default = self::HTML,
	) {
		$this->registry = $this->normalize(array_replace($this->builtins(), $escapers));
		self::assertName($default);

		if (!isset($this->registry[$default])) {
			throw self::unknown($default);
		}

		$this->default = $default;
	}

	#[\Override]
	public function get(string $name): Contract\Escaper
	{
		return $this->registry[$name] ?? throw self::unknown($name);
	}

	#[\Override]
	public function register(string $name, Contract\Escaper $escaper): void
	{
		self::assertName($name);
		$this->registry[$name] = $escaper;
	}

	/** @return array<non-empty-string, Contract\Escaper> */
	private function builtins(): array
	{
		return [
			self::HTML => new Escaper\Html(),
		];
	}

	/** @psalm-assert non-empty-string $name */
	private static function assertName(string $name): void
	{
		if ($name === '') {
			throw new UnexpectedValueException('Escaper name must be a non-empty string');
		}
	}

	/**
	 * @param array<array-key, Contract\Escaper> $escapers
	 * @return array<non-empty-string, Contract\Escaper>
	 */
	private function normalize(array $escapers): array
	{
		$normalized = [];

		foreach ($escapers as $name => $escaper) {
			if (!is_string($name)) {
				throw new UnexpectedValueException('Escaper name must be a non-empty string');
			}

			self::assertName($name);

			if (!$escaper instanceof Contract\Escaper) {
				throw new UnexpectedValueException(
					"Escaper `{$name}` must implement `Celema\\Boiler\\Contract\\Escaper`",
				);
			}

			$normalized[$name] = $escaper;
		}

		return $normalized;
	}

	private static function unknown(string $name): UnexpectedValueException
	{
		return new UnexpectedValueException("Unknown escaper `{$name}`");
	}
}
