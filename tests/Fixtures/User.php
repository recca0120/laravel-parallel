<?php

namespace Recca0120\AsyncTesting\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['email'];
}
