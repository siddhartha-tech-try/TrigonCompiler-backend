<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgrammingLanguage extends Model
{
    protected $table = 'programming_languages';

    protected $fillable = [
        'language_name',
        'file_extension',
        'paradigm',
        'file_name',
        'container_image',
        'run_command',
        'typing_discipline',
        'execution_type',
        'website',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
