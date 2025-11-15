<?php

namespace App\Service;

use App\Contract\FilterConditionInterface;
use App\Contract\FilterInterface;

readonly class HistoryQuotesService
{
    public function __construct(private FilterInterface $filter)
    {}

    public function getFilteredHistoryQuotes(iterable $items, FilterConditionInterface $conditions): iterable
    {
        return $this->filter->filter($items, $conditions);
    }
}
