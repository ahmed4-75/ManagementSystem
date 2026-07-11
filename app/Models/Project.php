<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
    */
    protected $fillable = ['title','description'];

    /**
     * The users that belong to the project.
    */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(user::class, 'project_user', 'project_id', 'user_id');
    }

    /**
     * Get the statuses for the blog project.
    */
    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class, 'project_id');
    }

    /**
     * Get the tasks for the blog project.
    */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }
}
