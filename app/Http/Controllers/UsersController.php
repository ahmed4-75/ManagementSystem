<?php

namespace App\Http\Controllers;

use App\Services\UserService;

class UsersController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}
}
