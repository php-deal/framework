<?php

declare(strict_types=1);

namespace Example\Inventory;

use DomainException;

/**
 * A stock count is lower than the quantity reserved for orders (R9)
 */
final class CountBelowReserved extends DomainException {}
