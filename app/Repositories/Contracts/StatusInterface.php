<?php

namespace App\Repositories\Contracts;

use App\Http\Requests\StatusRequest;
use App\Models\Status;

interface StatusInterface
{
    public function index(int $id);
    public function store(StatusRequest $request, int $id);
    public function update(StatusRequest $request, int $id);
    public function delete(Status $status);
}
