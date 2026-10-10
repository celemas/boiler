<?php

declare(strict_types=1);

namespace Celema\Boiler\Bench;

enum Stock: string
{
	case InStock = 'in-stock';
	case Low = 'low';
	case Preorder = 'preorder';
	case SoldOut = 'sold-out';
}
