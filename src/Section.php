<?php

declare(strict_types=1);

namespace Celema\Boiler;

/** @internal */
final class Section
{
	/** @var list<string> in call order */
	private array $prepended = [];

	/** @var list<array{list<int>, string}> position and content of each addition, in call order */
	private array $appended = [];

	/** The main content, null until `section()` captures it, even when empty. */
	private ?string $value = null;

	public function prepend(string $content): void
	{
		$this->prepended[] = $content;
	}

	/** @param list<int> $position as numbered by Sections */
	public function append(string $content, array $position): void
	{
		$this->appended[] = [$position, $content];
	}

	/**
	 * The additions of a layout stay closer to the main content than those of
	 * the template it wraps: page prepends, layout prepends, main content,
	 * layout appends, page appends. Call order gives that for prepends, as a
	 * layout renders after the template it wraps; appends are reordered. The
	 * appends of an insert, including those of its own layouts, stay together
	 * at the place of the insert. The default stands in for main content that
	 * was never captured.
	 */
	public function get(string $default = ''): string
	{
		$appended = $this->appended;
		usort(
			$appended,
			/**
			 * @param array{list<int>, string} $a
			 * @param array{list<int>, string} $b
			 */
			static fn(array $a, array $b): int => self::compare($a[0], $b[0]),
		);

		return implode('', $this->prepended) . ($this->value ?? $default) . implode('', array_column($appended, 1));
	}

	public function setValue(string $value): void
	{
		$this->value = $value;
	}

	/**
	 * Whether the template at position $a wraps the one at $b: it is a
	 * layout of that template or of a template it was inserted into, or a
	 * template that such a layout inserted. That is the case when the
	 * positions first differ in a layout level, as $a must render later and
	 * layout levels only grow while a template renders. Positions as
	 * numbered by Sections.
	 *
	 * @param list<int> $a
	 * @param list<int> $b
	 */
	public static function wraps(array $a, array $b): bool
	{
		return (self::divergence($a, $b) % 2) === 0;
	}

	/**
	 * Orders two appends at the first difference of their positions: a
	 * higher layout level first, otherwise call order.
	 *
	 * @param list<int> $a
	 * @param list<int> $b
	 */
	private static function compare(array $a, array $b): int
	{
		$index = self::divergence($a, $b);

		if (($index % 2) === 0) {
			return $b[$index] <=> $a[$index];
		}

		return $a[$index] <=> $b[$index];
	}

	/**
	 * The index of the first difference of two positions. Even parts are
	 * layout levels, odd parts call numbers. Call numbers are unique, so two
	 * positions always differ before either ends.
	 *
	 * @param list<int> $a
	 * @param list<int> $b
	 */
	private static function divergence(array $a, array $b): int
	{
		$index = 0;

		while ($a[$index] === $b[$index]) {
			$index++;
		}

		return $index;
	}
}
