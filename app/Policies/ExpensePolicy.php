<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class ExpensePolicy
{
    use AuthorizesBranchRecords;
}
