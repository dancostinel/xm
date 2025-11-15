<?php

namespace App\Contract;

interface FilterConditionInterface
{
    public function applyFilterConditions(iterable $item): bool;
}
