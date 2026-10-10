<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

final readonly class Product
{
	/**
	 * @mago-expect lint:excessive-parameter-list A view model: its fields are what the product templates show.
	 *
	 * @param list<string> $tags
	 * @param list<string> $badges
	 * @param list<Image> $gallery
	 * @param array<string, string> $specs
	 * @param list<array{label: string, sku: string, price: float, available: bool}> $variants
	 */
	public function __construct(
		public int $id,
		public string $sku,
		public string $name,
		public string $vendor,
		public Url $url,
		public Image $image,
		public float $price,
		public float $compareAt,
		public Stock $stock,
		public int $stockCount,
		public int $rating,
		public int $reviews,
		public array $tags,
		public array $badges,
		public bool $freeShipping,
		public array $gallery = [],
		public array $specs = [],
		public array $variants = [],
		public ?Html $description = null,
	) {}

	public function onSale(): bool
	{
		return $this->compareAt > $this->price;
	}

	public function discount(): int
	{
		return (int) round((1 - ($this->price / $this->compareAt)) * 100);
	}
}
