<?php

declare(strict_types=1);

namespace ItmoSoft\Multitenancy\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use ItmoSoft\Multitenancy\Database\Concerns\BelongsToTenant;

class Project extends Model
{
    use BelongsToTenant;

    protected $table = 'projects';

    protected $fillable = ['name'];
}
