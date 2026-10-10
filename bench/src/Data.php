<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

use ArrayIterator;
use DateTimeImmutable;
use DateTimeZone;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Generates the view data of the benchmark pages. A fixed seed keeps every run
 * and every engine on the same data.
 *
 * @mago-expect lint:kan-defect Fixture data is loops and conditions, with no logic worth splitting up.
 */
final class Data
{
	private const int SEED = 20_261_010;
	private const int PRODUCTS = 36;
	private const int REVIEWS = 10;
	private const int RELATED = 6;

	private const array ADJECTIVES = [
		'Ultra',
		'Compact',
		'Wireless',
		'Ergonomic',
		'Portable',
		'Smart',
		'Classic',
		'Pro',
		'Slim',
		'Rugged',
	];

	private const array NOUNS = [
		'Laptop 14"',
		'Mouse',
		'USB-C Hub',
		'Mechanical Keyboard',
		'27" Monitor',
		'Webcam',
		'Desk Lamp',
		'Office Chair',
		'Standing Desk',
		'Notebook Set',
		'Headset',
		'Docking Station',
		'Laptop Sleeve & Stand',
		'Cable Kit',
		'Monitor Arm',
		'Foot Rest',
	];

	private const array VENDORS = [
		'Acme Tech',
		'Pixel Works',
		'Dock Labs',
		'Key Forge',
		'VisionX',
		'Stream & Co',
		'Lumi Home',
		'Forma Seat',
		'Rise Labs',
		"O'Neil Paper",
	];

	private const array TAGS = [
		'electronics',
		'office',
		'accessories',
		'displays',
		'audio',
		'furniture',
		'lighting',
		'travel',
		'eco',
	];

	private const array BADGES = ['bestseller', 'spring deal', 'staff pick', 'bundle & save', 'made in EU'];

	private const array REVIEWERS = [
		'Alex M.',
		'Sam & Robin',
		'Chris <cg>',
		'Jordan P.',
		"Dana O'Brien",
		'Kim L.',
		'Taylor R.',
		'Morgan S.',
	];

	private const array REVIEW_TITLES = [
		'Does what it says',
		'Great value & fast delivery',
		'Solid, but the manual is thin',
		'Better than my old one',
		'"Pro" is not an exaggeration',
		'Good, not great',
		'Would buy again',
	];

	private const array SENTENCES = [
		'Setup took less than ten minutes and everything worked on the first try.',
		'The build quality is better than I expected at this price.',
		'I use it every day for work & travel, and it has not let me down.',
		'Battery life is fine, although "all day" is a stretch.',
		'The cable is a little short if your desk is wider than 160 cm.',
		'Support answered within an hour when I asked about the warranty.',
		'It replaced three other gadgets on my desk <finally>.',
		'Fan noise stays low unless you push it really hard.',
	];

	/**
	 * Data that every page of the site shows, registered once with each
	 * engine.
	 *
	 * @return array{site: Site, nav: list<NavItem>, footer: list<array<string, mixed>>}
	 */
	public static function shared(): array
	{
		return [
			'site' => new Site(
				name: 'Celema & Partners Store',
				tagline: 'Tools for people who work at a desk',
				locale: 'en',
				year: 2026,
				support: new Support('support@example.com', '+49 951 000000', 'Mon–Fri 9:00–17:00'),
			),
			'nav' => self::nav(),
			'footer' => self::footer(),
		];
	}

	/**
	 * The context of each page, keyed by its template name.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function pages(int $scale = 1): array
	{
		$random = new Randomizer(new Mt19937(self::SEED));
		$request = self::request();
		$shop = self::shop();

		return [
			'listing' => $request + $shop + self::listing($random, $scale),
			'product' => $request + $shop + self::product($random, $scale),
			'article' => $request + self::article($random, $scale),
		];
	}

	/** @return list<NavItem> */
	private static function nav(): array
	{
		$sections = [
			'Laptops & Tablets',
			'Monitors',
			'Keyboards & Mice',
			'Audio',
			'Office Furniture',
			'Lighting',
			'Sale',
		];
		$children = ['New arrivals', 'Bestsellers', 'For business', 'Accessories', 'Refurbished', 'Gift ideas'];
		$nav = [];

		foreach ($sections as $index => $section) {
			$path = '/c/' . self::slug($section);
			$nav[] = new NavItem(
				$section,
				$path,
				$index === 0,
				array_map(
					static fn(string $child): NavItem => new NavItem(
						$child,
						$path . '/' . self::slug($child) . '?ref=nav&section=' . $index,
					),
					// The last section has no submenu.
					$index === (count($sections) - 1) ? [] : $children,
				),
			);
		}

		return $nav;
	}

	/** @return list<array<string, mixed>> */
	private static function footer(): array
	{
		$columns = [
			'Shop' => ['All products', 'Deals & offers', 'Gift cards', 'Brands', 'New arrivals', 'Outlet'],
			'Service' => ['Shipping & returns', 'Order status', 'Warranty', 'Repairs', 'Contact', 'FAQ'],
			'Company' => ['About us', 'Careers', 'Press', 'Sustainability', 'Stores', 'Magazine'],
			'Legal' => ['Terms & conditions', 'Privacy', 'Cookies', 'Imprint', 'Accessibility', 'Licenses'],
		];
		$footer = [];

		foreach ($columns as $title => $links) {
			$footer[] = [
				'title' => $title,
				'links' => array_map(
					static fn(string $label): array => [
						'label' => $label,
						'url' => '/' . self::slug($title) . '/' . self::slug($label),
					],
					$links,
				),
			];
		}

		return $footer;
	}

	/**
	 * Values that depend on the visitor.
	 *
	 * @return array<string, mixed>
	 */
	private static function request(): array
	{
		return [
			'user' => new User(
				42,
				"Jane O'Neil & Co",
				'jane@example.com',
				'gold',
				'/img/avatars/42.jpg?size=64&crop=1',
			),
			'cart' => [
				'items' => 4,
				'lines' => [
					['name' => 'USB-C Cable 2m', 'qty' => 2, 'total' => 39.98],
					['name' => 'Laptop Sleeve & Stand', 'qty' => 1, 'total' => 39.95],
					['name' => 'Monitor Arm <Dual>', 'qty' => 1, 'total' => 58.50],
				],
				'subtotal' => 138.43,
			],
			'campaign' => [
				'title' => 'Spring flash deal',
				'code' => 'SPRING20',
				'threshold' => 150.00,
				'endsAt' => self::date('2026-05-01 20:00:00'),
			],
			'announcement' => new Html('<strong>Holiday sale:</strong> 20% off all items until Sunday.'),
		];
	}

	/**
	 * Values of the pages inside the shop layout.
	 *
	 * @return array<string, mixed>
	 */
	private static function shop(): array
	{
		$names = [
			'Laptops & Notebooks',
			'Tablets',
			'Monitors',
			'Keyboards',
			'Mice & Trackpads',
			'Headsets',
			'Desks',
			'Chairs',
		];
		$categories = [];

		foreach ($names as $index => $name) {
			$categories[] = [
				'name' => $name,
				'url' => '/c/' . self::slug($name),
				'count' => 312 - ($index * 31),
				'current' => $index === 0,
			];
		}

		return ['categories' => $categories];
	}

	/** @return array<string, mixed> */
	private static function listing(Randomizer $random, int $scale): array
	{
		$count = self::PRODUCTS * $scale;
		$products = [];

		for ($position = 1; $position <= $count; $position++) {
			$products[] = self::item($random, 100 + $position, 'listing', $position);
		}

		$url = '/c/laptops-notebooks';

		return [
			'category' => [
				'name' => 'Laptops & Notebooks',
				'slug' => 'laptops-notebooks',
				'url' => $url,
				'description' => 'Thin, light & powerful: every laptop we stock ships with a 3-year "no questions" warranty.',
			],
			'products' => $products,
			'facets' => self::facets(),
			'activeFilters' => [
				['label' => 'Brand', 'value' => 'Acme Tech', 'remove' => $url . '?price=under-500&rating=4'],
				['label' => 'Price', 'value' => 'Under $500', 'remove' => $url . '?brand=acme-tech&rating=4'],
				['label' => 'Rating', 'value' => '4 stars & up', 'remove' => $url . '?brand=acme-tech&price=under-500'],
			],
			'sort' => 'price-asc',
			'sortOptions' => [
				'popular' => 'Most popular',
				'price-asc' => 'Price: low to high',
				'price-desc' => 'Price: high to low',
				'rating' => 'Customer rating',
				'new' => 'New & trending',
			],
			'pagination' => [
				'page' => 2,
				'pages' => 9,
				'from' => $count + 1,
				'to' => $count * 2,
				'total' => $count * 9,
				'url' => $url . '?sort=price-asc&page=',
			],
			'breadcrumbs' => new ArrayIterator([
				['label' => 'Home', 'url' => '/'],
				['label' => 'Shop', 'url' => '/c'],
				['label' => 'Laptops & Notebooks', 'url' => $url],
			]),
		];
	}

	/** @return list<array<string, mixed>> */
	private static function facets(): array
	{
		$groups = [
			'brand' => ['Brand', array_slice(self::VENDORS, 0, 6)],
			'price' => ['Price', ['Under $100', '$100 – $250', '$250 – $500', '$500 – $1,000', '$1,000 & up']],
			'rating' => ['Rating', ['4 stars & up', '3 stars & up', '2 stars & up']],
			'screen' => ['Screen size', ['13"', '14"', '15"', '16"', '17" & larger']],
			'availability' => ['Availability', ['In stock', 'Preorder', 'Include sold out']],
		];
		$facets = [];

		foreach ($groups as $key => [$title, $labels]) {
			$options = [];

			foreach ($labels as $index => $label) {
				$options[] = [
					'label' => $label,
					'value' => self::slug($label),
					'count' => 7 + ((strlen($label) * ($index + 3)) % 90),
					'selected' => $index === 0 && $key !== 'screen' && $key !== 'availability',
				];
			}

			$facets[] = [
				'key' => $key,
				'title' => $title,
				'expanded' => $key !== 'availability',
				'options' => $options,
			];
		}

		return $facets;
	}

	/** @return array<string, mixed> */
	private static function product(Randomizer $random, int $scale): array
	{
		$base = self::item($random, 1001, 'product', 1);
		$gallery = [];

		for ($index = 1; $index <= 5; $index++) {
			$gallery[] = new Image(
				"/img/products/1001-{$index}.jpg?w=960&h=720",
				"Ultra Laptop 14\" – view {$index}",
				960,
				720,
			);
		}

		$product = new Product(
			id: $base->id,
			sku: 'UL-14-512',
			name: 'Ultra Laptop 14"',
			vendor: 'Acme Tech',
			url: new Url('/p/ultra-laptop-14'),
			image: $gallery[0],
			price: 1299.99,
			compareAt: 1499.99,
			stock: Stock::Low,
			stockCount: 3,
			rating: 5,
			reviews: self::REVIEWS * $scale,
			tags: ['new', 'electronics', 'travel'],
			badges: ['bestseller', 'spring deal'],
			freeShipping: true,
			gallery: $gallery,
			specs: [
				'Display' => '14" IPS, 2880 × 1800, 120 Hz',
				'Processor' => '12-core, up to 4.8 GHz',
				'Memory' => '32 GB',
				'Storage' => '512 GB SSD (NVMe)',
				'Graphics' => 'Integrated <Xe>',
				'Battery' => '72 Wh, up to 14 h',
				'Ports' => '2 × USB-C, 1 × USB-A, HDMI 2.1 & audio',
				'Wireless' => 'Wi-Fi 6E, Bluetooth 5.3',
				'Camera' => '1080p with privacy shutter',
				'Keyboard' => 'Backlit, 1.5 mm travel',
				'Weight' => '1.29 kg',
				'Dimensions' => '312 × 221 × 15.9 mm',
				'Operating system' => 'None ("bring your own")',
				'Warranty' => '3 years on-site',
			],
			variants: [
				['label' => '16 GB / 256 GB', 'sku' => 'UL-14-256', 'price' => 1099.99, 'available' => true],
				['label' => '32 GB / 512 GB', 'sku' => 'UL-14-512', 'price' => 1299.99, 'available' => true],
				['label' => '32 GB / 1 TB', 'sku' => 'UL-14-1T', 'price' => 1499.99, 'available' => true],
				['label' => '64 GB / 2 TB', 'sku' => 'UL-14-2T', 'price' => 1999.99, 'available' => false],
			],
			description: new Html(
				'<p>The <strong>Ultra Laptop 14"</strong> is built for people who carry their office with them. '
					. 'It weighs 1.29&nbsp;kg, runs for a full working day, and wakes up in under a second.</p>'
					. '<p>Read our <a href="/magazine/desk-setup">desk setup guide</a> for matching accessories.</p>',
			),
		);

		$reviews = [];

		for ($index = 0; $index < (self::REVIEWS * $scale); $index++) {
			$reviews[] = new Review(
				author: self::pick($random, self::REVIEWERS),
				rating: $random->getInt(3, 5),
				title: self::pick($random, self::REVIEW_TITLES),
				body: self::pick($random, self::SENTENCES) . ' ' . self::pick($random, self::SENTENCES),
				date: self::date('2026-04-01 12:00:00')->modify('-' . ($index * 3) . ' days'),
				verified: $random->getInt(0, 3) > 0,
			);
		}

		$related = [];

		for ($position = 1; $position <= (self::RELATED * $scale); $position++) {
			$related[] = self::item($random, 2000 + $position, 'related', $position);
		}

		return [
			'product' => $product,
			'reviews' => $reviews,
			'related' => $related,
			'breadcrumbs' => new ArrayIterator([
				['label' => 'Home', 'url' => '/'],
				['label' => 'Shop', 'url' => '/c'],
				['label' => 'Laptops & Notebooks', 'url' => '/c/laptops-notebooks'],
				['label' => 'Ultra Laptop 14"', 'url' => '/p/ultra-laptop-14'],
			]),
		];
	}

	/** @return array<string, mixed> */
	private static function article(Randomizer $random, int $scale): array
	{
		$blocks = [];

		for ($part = 0; $part < $scale; $part++) {
			array_push($blocks, ...self::blocks($random, $part));
		}

		return [
			'article' => [
				'title' => "How to build a desk setup that doesn't hurt",
				'lead' => 'Screen height, chair & light matter more than the price tag. A practical guide in <10 minutes.',
				'author' => new Author(
					'Robin Castillo',
					'Workplace editor',
					'Robin has tested desks, chairs & "ergonomic" gadgets since 2014.',
					'/magazine/authors/robin-castillo',
				),
				'published' => self::date('2026-03-14 08:30:00'),
				'readingMinutes' => 7 * $scale,
				'tags' => ['Ergonomics', 'Home office', 'Guides', 'Monitors', 'Chairs & Desks'],
			],
			'blocks' => $blocks,
			'breadcrumbs' => new ArrayIterator([
				['label' => 'Home', 'url' => '/'],
				['label' => 'Magazine', 'url' => '/magazine'],
				['label' => 'Guides & how-tos', 'url' => '/magazine/guides'],
			]),
		];
	}

	/**
	 * One part of the article: the content blocks an editor arranged, each
	 * rendered by the template of its type.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function blocks(Randomizer $random, int $part): array
	{
		$id = static fn(int $block): int => ($part * 100) + $block;
		$text = static fn(): array => ['type' => 'text', 'html' => self::richText($random)];
		$heading = static fn(int $block, int $level, string $title): array => [
			'type' => 'heading',
			'level' => $level,
			'id' => 'section-' . $id($block),
			'text' => $title,
		];
		$products = [];

		for ($position = 1; $position <= 3; $position++) {
			$products[] = self::item($random, 3000 + $id($position), 'article', $position);
		}

		$rows = [];

		foreach ([
			'13" laptop',
			'24" monitor',
			'27" monitor',
			'34" ultrawide',
			'Two 24" monitors',
		] as $index => $screen) {
			$rows[] = [
				$screen,
				(50 + ($index * 10)) . ' cm',
				(60 + ($index * 15)) . ' – ' . (80 + ($index * 15)) . ' cm',
				$index > 2 ? 'Yes & recommended' : 'Optional',
				$index === 0 ? 'Use a stand <always>' : 'Top edge at eye level',
			];
		}

		return [
			$text(),
			$heading(1, 2, 'Start with the screen & work down'),
			$text(),
			[
				'type' => 'image',
				'src' => '/img/magazine/desk-' . $id(1) . '.jpg?w=1200&h=675',
				'alt' => 'A desk with a monitor at eye level',
				'width' => 1200,
				'height' => 675,
				'caption' => 'The top edge of the screen sits at eye level.',
				'credit' => 'Studio K & Sons',
			],
			$heading(2, 3, 'Viewing distance by screen size'),
			$text(),
			[
				'type' => 'table',
				'head' => ['Screen', 'Height', 'Distance', 'Monitor arm', 'Tip'],
				'rows' => $rows,
			],
			[
				'type' => 'quote',
				'text' => 'The best chair is the one you leave every 30 minutes — "sitting right" is a myth.',
				'cite' => 'Dr. Lena Hoffmann, occupational physician',
			],
			$heading(3, 2, 'Light: bright, indirect & from the side'),
			$text(),
			[
				'type' => 'video',
				'id' => $id(1),
				'provider' => 'vimeo',
				'url' => 'https://video.example.com/watch?v=desk-' . $id(1) . '&t=30',
				'title' => 'Adjusting a monitor arm in 3 steps',
			],
			[
				'type' => 'products',
				'title' => 'What we used for this setup',
				'products' => $products,
			],
			$heading(4, 2, 'Questions readers ask'),
			[
				'type' => 'faq',
				'title' => 'Short answers',
				'items' => [
					['question' => 'Is a standing desk worth it?', 'answer' => 'Yes, if you change position often.'],
					['question' => 'How high should my chair be?', 'answer' => 'Feet flat, knees at about 90°.'],
					[
						'question' => 'Do I need a "gaming" chair?',
						'answer' => 'No. Look for adjustable lumbar support.',
					],
					['question' => 'One large monitor or two?', 'answer' => 'Two if you compare documents & code.'],
					['question' => 'What about blue light glasses?', 'answer' => 'The evidence is thin <so far>.'],
				],
			],
			$text(),
			[
				'type' => 'image',
				'src' => '/img/magazine/lamp-' . $id(2) . '.jpg?w=1200&h=675',
				'alt' => 'A desk lamp placed to the left of the keyboard',
				'width' => 1200,
				'height' => 675,
				'caption' => 'Place the lamp opposite your writing hand.',
			],
			[
				'type' => 'quote',
				'text' => 'Buy the chair first & the desk second.',
				'cite' => 'Robin Castillo',
			],
			$text(),
		];
	}

	private static function richText(Randomizer $random): Html
	{
		$paragraphs = [];

		for ($index = 0; $index < 2; $index++) {
			$paragraphs[] =
				'<p>'
				. htmlspecialchars(self::pick($random, self::SENTENCES))
				. ' <em>'
				. htmlspecialchars(self::pick($random, self::SENTENCES))
				. '</em> <a href="/magazine/guides?topic=ergonomics&amp;page='
				. $random->getInt(1, 9)
				. '">Read more</a>.</p>';
		}

		return new Html(implode('', $paragraphs));
	}

	private static function item(Randomizer $random, int $id, string $ref, int $position): Product
	{
		$name = self::ADJECTIVES[$id % count(self::ADJECTIVES)] . ' ' . self::NOUNS[($id * 7) % count(self::NOUNS)];
		$vendor = self::pick($random, self::VENDORS);
		$price = $random->getInt(19, 1499) + (self::pick($random, [0, 50, 95, 99]) / 100);
		$compareAt = $random->getInt(0, 9) < 4 ? round($price * (1 + ($random->getInt(10, 30) / 100)), 2) : $price;
		$stock = self::pick($random, [
			Stock::InStock,
			Stock::InStock,
			Stock::InStock,
			Stock::InStock,
			Stock::InStock,
			Stock::InStock,
			Stock::Low,
			Stock::Low,
			Stock::Preorder,
			Stock::SoldOut,
		]);
		$tags = [];

		if ($random->getInt(0, 4) === 0) {
			$tags[] = 'new';
		}

		foreach ($random->pickArrayKeys(self::TAGS, 2) as $key) {
			$tags[] = self::TAGS[$key];
		}

		$badges = [];

		foreach ($random->pickArrayKeys(self::BADGES, 2) as $index => $key) {
			if ($random->getInt(0, 2) > $index) {
				$badges[] = self::BADGES[$key];
			}
		}

		return new Product(
			id: $id,
			sku: strtoupper(substr(self::slug($vendor), 0, 3)) . '-' . $id,
			name: $name,
			vendor: $vendor,
			url: new Url('/p/' . self::slug($name) . '-' . $id, ['ref' => $ref, 'pos' => $position]),
			image: new Image("/img/products/{$id}.jpg?w=320&h=240", "{$name} by {$vendor}", 320, 240),
			price: $price,
			compareAt: $compareAt,
			stock: $stock,
			stockCount: match ($stock) {
				Stock::InStock => $random->getInt(6, 60),
				Stock::Low => $random->getInt(1, 5),
				default => 0,
			},
			rating: $random->getInt(3, 5),
			reviews: $random->getInt(0, 400),
			tags: $tags,
			badges: $badges,
			freeShipping: $price >= 150,
		);
	}

	/**
	 * @template T
	 *
	 * @param list<T> $values
	 *
	 * @return T
	 */
	private static function pick(Randomizer $random, array $values): mixed
	{
		return $values[$random->getInt(0, count($values) - 1)];
	}

	private static function slug(string $text): string
	{
		return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
	}

	private static function date(string $time): DateTimeImmutable
	{
		return new DateTimeImmutable($time, new DateTimeZone('UTC'));
	}
}
