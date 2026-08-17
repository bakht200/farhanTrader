<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class OrderPolicy
{
    use AuthorizesBranchRecords;
}
