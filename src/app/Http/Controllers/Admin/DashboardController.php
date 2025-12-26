<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // Illuminate\Http\Request は使っていませんが、習慣的に追加してもOKです
use Inertia\Inertia; // 💡 修正点1: Inertiaをインポートする

class DashboardController extends Controller
{
    /**
     * 管理者ダッシュボードを表示する
     *
     * @return \Inertia\Response // 💡 戻り値の型を Inertia\Response に変更
     */
    public function index()
    {
        // 💡 修正点2: view() ではなく Inertia::render() を使用する
        // 'Admin/Dashboard' は、通常 resources/js/Pages/Admin/Dashboard.vue (または .jsx) を指します。
        return Inertia::render('Admin/Dashboard', [
            // 必要に応じて管理画面に表示するデータを渡します
            'status' => session('status'),
            'user' => auth('admin')->user(),
            'currentDate' => \Carbon\Carbon::now()->format('Y年m月d日'),
        ]);
    }
}
