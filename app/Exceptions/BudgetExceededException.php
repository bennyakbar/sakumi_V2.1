<?php

namespace App\Exceptions;

class BudgetExceededException extends \RuntimeException
{
    public function __construct(string $budgetWarning)
    {
        parent::__construct($budgetWarning);
    }
}
