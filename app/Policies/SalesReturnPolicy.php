<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class SalesReturnPolicy
{
    use AuthorizesBranchRecords;
}
