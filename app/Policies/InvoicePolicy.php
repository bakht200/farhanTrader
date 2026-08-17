<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class InvoicePolicy
{
    use AuthorizesBranchRecords;
}
