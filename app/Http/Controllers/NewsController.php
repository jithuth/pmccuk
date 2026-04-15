<?php namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::where('status', 'published')->orderBy('created_at', 'desc')->paginate(12);
        return view('news', compact('news'));
    }

    public function show($id)
    {
        $article = News::findOrFail($id);
        return view('news_details', compact('article'));
    }
}
