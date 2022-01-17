<?php

namespace Recca0120\LaravelParallel\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['email', 'password', 'api_token'];
}
