<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class SalePolicy
{
    use AuthorizesBranchRecords;
}
