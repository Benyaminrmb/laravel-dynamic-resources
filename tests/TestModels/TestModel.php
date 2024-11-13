<?php

namespace Benyaminrmb\LaravelDynamicResources\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $fillable = ['id', 'name', 'email', 'details'];
}
