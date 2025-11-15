<?php

namespace App\Service;

use App\Contract\FilterConditionInterface;
use App\Contract\FilterInterface;

class DataFilterService implements FilterInterface
{
    /**
     * Filters the data by symbol, startDate and endDate
     */
    public function filter(iterable $items, FilterConditionInterface $conditions): iterable
    {
        foreach ($items as $item) {
            if ($conditions->applyFilterConditions($item)) {
                yield $item;
            }
        }
    }
}
