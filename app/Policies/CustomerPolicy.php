<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class CustomerPolicy
{
    use AuthorizesBranchRecords;
}
