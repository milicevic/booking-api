<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum DeployStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case PendingDeploy = 'pending_deploy';
    case Deployed = 'deployed';
}
