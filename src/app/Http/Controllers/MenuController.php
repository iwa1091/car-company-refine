<?php

namespace App\Http\Controllers;

use App\Models\Menu; // Menuモデルをインポート
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * メニュー・料金ページを表示する
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // データベースからメニュー情報を取得
        $eyelashMenus = Menu::where('category', 'まつげエクステンション')->get();
        $eyebrowMenus = Menu::where('category', '眉メニュー')->get();
        $setMenus     = Menu::where('category', 'お得なセットメニュー')->get();

        // 取得したデータをビューに渡す
        return view('menu_price', compact('eyelashMenus', 'eyebrowMenus', 'setMenus'));
    }
}