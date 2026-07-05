<?php

namespace App\Repositories\Contracts;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;

interface ProjectInterface
{
    public function index();
    public function ProjectsUser();
    public function show(int $id);
    public function store(ProjectRequest $request);
    public function update(ProjectRequest $request, int $id);
    public function delete(Project $project);
}
