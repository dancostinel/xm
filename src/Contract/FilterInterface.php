<?php

namespace App\Contract;

interface FilterInterface
{
    public function filter(iterable $items, FilterConditionInterface $conditions): iterable;
}
