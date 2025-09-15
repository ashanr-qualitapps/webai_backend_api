<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class SwaggerController extends Controller
{
    public function index()
    {
        $jsonFile = storage_path('api-docs/api-docs.json');
        
        if (!File::exists($jsonFile)) {
            // Generate the documentation if it doesn't exist
            Artisan::call('l5-swagger:generate');
        }
        
        $swaggerJson = File::get($jsonFile);
        
        return view('swagger.index', compact('swaggerJson'));
    }
    
    public function json()
    {
        $jsonFile = storage_path('api-docs/api-docs.json');
        
        if (!File::exists($jsonFile)) {
            Artisan::call('l5-swagger:generate');
        }
        
        return response()->file($jsonFile);
    }
}