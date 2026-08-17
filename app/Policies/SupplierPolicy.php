<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class SupplierPolicy
{
    use AuthorizesBranchRecords;
}
