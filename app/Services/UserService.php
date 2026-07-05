<?php

namespace App\Services;

use App\Repositories\Contracts\UserInterface;

class UserService
{
    public function __construct(
        protected UserInterface $userRepository
    ) {}

    // public function index()
    // {
    //     return $this->userRepository->index();
    // }

    // public function show(int $id)
    // {
    //     return $this->userRepository->show($id);
    // }

    // public function store(array $data)
    // {
    //     return $this->userRepository->store($data);
    // }

    // public function update(array $data, int $id)
    // {
    //     return $this->userRepository->update($data, $id);
    // }

    // public function delete(int $id)
    // {
    //     return $this->userRepository->delete($id);
    // }
}
