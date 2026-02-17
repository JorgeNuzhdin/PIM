<?php

namespace App\Http\Controllers;

use App\Models\Problema;
use Illuminate\Http\Request;

class HomePageController extends Controller
{
    public function index()
    {
        $totalProblemas = Problema::count();

        $problemaAleatorio = Problema::whereBetween('difficulty', [2, 3])
            ->inRandomOrder()
            ->first();

        return view('homepage', compact('totalProblemas', 'problemaAleatorio'));
    }
}