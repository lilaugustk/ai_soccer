<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WorldCupController extends Controller
{
    public function index()
    {
        return Inertia::render('WorldCup/Index');
    }

    public function team($id)
    {
        return redirect()->route('worldcup.index');
    }

    public function group($letter)
    {
        return redirect()->route('worldcup.index');
    }

    public function match($id)
    {
        return redirect()->route('worldcup.index');
    }
}
