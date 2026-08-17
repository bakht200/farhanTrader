<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class BranchSharePolicy
{
    use AuthorizesBranchRecords;
}
