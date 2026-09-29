<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Escaper\Html;
use Celema\Boiler\Exception\UnexpectedValueException;
use Celema\Boiler\Proxy\ArrayProxy;
use Celema\Boiler\Proxy\IteratorProxy;
use Celema\Boiler\Proxy\ObjectProxy;
use Celema\Boiler\Proxy\Proxy;
use Celema\Boiler\Proxy\StringProxy;
use Override;
use Traversable;

/** @api */
final class Wrapper implements Contract\Wrapper
{
	private readonly Contract\Escapers $escapers;
	private ?Contract\Filters $filters;
	private readonly Contract\Escaper $defaultEscaper;
	private readonly bool $isBuiltinEscaper;

	/** @var list<class-string> */
	private array $trusted = [];

	public function __construct(
		?Contract\Escapers $escapers = null,
		?Contract\Filters $filters = null,
	) {
		$this->escapers = $escapers ?? new Escapers();
		$this->defaultEscaper = $this->escapers->get($this->escapers->default);
		$this->isBuiltinEscaper = $this->defaultEscaper instanceof Html;
		$this->filters = $filters;
	}

	#[Override]
	public function withTrusted(array $trusted): static
	{
		if ($trusted === $this->trusted) {
			return $this;
		}

		$clone = clone $this;
		$clone->trusted = $trusted;

		return $clone;
	}

	#[Override]
	public function wrap(mixed $value): mixed
	{
		if (is_string($value)) {
			return new StringProxy($value, $this);
		}

		if (is_array($value)) {
			return new ArrayProxy($value, $this);
		}

		if (
			$value === null
			|| is_int($value)
			|| is_float($value)
			|| is_bool($value)
			|| is_resource($value)
		) {
			return $value;
		}

		if ($value instanceof Proxy) {
			return $value;
		}

		// Trust applies at any depth: an object of a trusted class is left
		// alone whether it sits in the context, in an array, or behind an
		// iterator. Checked before Traversable so trusted collections stay
		// unwrapped too.
		if ($this->isTrusted($value)) {
			return $value;
		}

		if ($value instanceof Traversable) {
			return new IteratorProxy($value, $this);
		}

		if (is_object($value)) {
			return new ObjectProxy($value, $this);
		}

		throw new UnexpectedValueException('Unsupported template value type');
	}

	#[Override]
	public function unwrap(mixed $value): mixed
	{
		if ($value instanceof Proxy) {
			return $value->unwrap();
		}

		if (!is_array($value)) {
			return $value;
		}

		return array_map($this->unwrap(...), $value);
	}

	#[Override]
	public function escape(
		string $value,
		?string $escaper = null,
	): string {
		if ($escaper === null) {
			return $this->isBuiltinEscaper
				? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
				: $this->defaultEscaper->escape($value);
		}

		return $this->escapers->get($escaper)->escape($value);
	}

	#[Override]
	public function filter(string $name): Contract\Filter
	{
		return $this->filters()->get($name);
	}

	private function filters(): Contract\Filters
	{
		return $this->filters ??= new Filters();
	}

	private function isTrusted(mixed $value): bool
	{
		if ($this->trusted === [] || !is_object($value)) {
			return false;
		}

		foreach ($this->trusted as $class) {
			if ($value instanceof $class) {
				return true;
			}
		}

		return false;
	}
}
