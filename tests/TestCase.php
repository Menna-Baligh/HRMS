<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Users created for role‑based feature tests.
     *
     * @var \App\Models\User|null
     */
    protected $employeeUser;
    protected $managerUser;
    protected $hrUser;

    /**
     * Employee models linked to the above users.
     *
     * @var \App\Models\Employee|null
     */
    protected $employee;
    protected $managerEmployee;
    protected $hrEmployee;
}
