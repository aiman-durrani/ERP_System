<?php

namespace App\Models;

use App\Traits\BelongsToHR;
use App\Enums\JobType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes, BelongsToHR;

    protected $fillable = [
        'hr_id',
        'job_category_id',
        'branch_id',
        'department_id',
        'title',
        'slug',
        'job_type',
        'number_of_positions',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'closing_date',
        'expected_joining_date',
        'status',
    ];

    protected $casts = [
        'job_type' => JobType::class,
        'closing_date' => 'date',
        'expected_joining_date' => 'date',
        'number_of_positions' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
