<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeforeAfterCase;
use App\Models\GalleryImage;
use App\Models\Review;

class GalleryController extends Controller
{
    /**
     * ギャラリーページを表示する.
     */
    public function index()
    {
        // データベースから各データを取得
        $beforeAfterCases = BeforeAfterCase::all();
        $galleryImages = GalleryImage::all();
        $reviews = Review::all();

        // 取得したデータをビューに渡す
        return view('gallery', [
            'beforeAfterCases' => $beforeAfterCases,
            'galleryImages' => $galleryImages,
            'reviews' => $reviews,
        ]);
    }
}