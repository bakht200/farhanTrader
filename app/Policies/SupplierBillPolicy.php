<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBranchRecords;

class SupplierBillPolicy
{
    use AuthorizesBranchRecords;
}
