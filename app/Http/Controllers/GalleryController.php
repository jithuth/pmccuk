<?php namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Gallery;
use App\Models\Video;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $albumId = $request->input('album_id');

        if ($albumId) {
            $album = Album::findOrFail($albumId);
            $images = Gallery::where('album_id', $albumId)->orderBy('id', 'desc')->paginate(16);
            return view('gallery', compact('images', 'album'));
        }

        // Show all albums with their cover photo or first photo
        $albums = Album::withCount('photos')->orderBy('id', 'desc')->paginate(12);
        return view('albums', compact('albums'));
    }

    public function videos()
    {
        $videos = Video::orderBy('id', 'desc')->paginate(12);
        return view('videos', compact('videos'));
    }
}
