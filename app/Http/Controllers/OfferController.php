<?php namespace App\Http\Controllers;

use App\Models\SponsorOffer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index()
    {
        $offers = SponsorOffer::where('is_active', 1)
            ->orderBy('order_no')
            ->get();
        return view('offers', compact('offers'));
    }
}
