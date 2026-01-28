<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgrammingLanguage;

class ProgrammingLanguageController extends Controller
{
    public function index()
    {
        return ProgrammingLanguage::query()
            ->where('is_active', true)
            ->orderBy('language_name')
            ->get([
                'id',
                'language_name',
                'file_extension',
                'code_preview',
                'execution_type',
            ]);
    }
}
