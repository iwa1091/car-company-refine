<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusinessHour;
use Inertia\Inertia;

class BusinessHourController extends Controller
{
    /**
     * 管理画面（Inertia）表示用
     * URL: /admin/business-hours
     */
    public function index()
    {
        return Inertia::render('Admin/BusinessHours');
    }

    /**
     * ================================
     * 🔹 [旧API] 曜日単位の営業時間取得
     * ================================
     * （既存構成との互換性維持用）
     */
    public function getHours()
    {
        $hours = BusinessHour::orderByRaw("
            FIELD(day_of_week, '月', '火', '水', '木', '金', '土', '日')
        ")->get();

        if ($hours->isEmpty()) {
            $hours = collect(BusinessHour::defaultHours());
        }

        return response()->json($hours);
    }

    /**
     * ================================
     * 🔹 [旧API] 曜日単位の営業時間更新
     * ================================
     */
    public function updateHours(Request $request)
    {
        $hours = $request->all();

        foreach ($hours as $hour) {
            // 更新するデータを確認し、is_closed の処理を適切に反映
            $open_time = $hour['is_closed'] ? null : $hour['open_time'];
            $close_time = $hour['is_closed'] ? null : $hour['close_time'];

            BusinessHour::updateOrCreate(
                ['day_of_week' => $hour['day_of_week']],
                [
                    'open_time' => $open_time,
                    'close_time' => $close_time,
                    'is_closed' => $hour['is_closed'] ?? false,
                ]
            );
        }

        return response()->json(['message' => '営業時間を更新しました'], 200);
    }

    // =======================================================================
    // 🆕 以下が「月 × 週 × 曜日」対応の新API群
    // =======================================================================

    /**
     * 指定した年月の週単位データを取得
     * GET /api/business-hours/weekly?year=2025&month=10
     */
    public function getWeekly(Request $request)
    {
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        // データが存在しない場合は自動で生成（第1〜第5週）
        if (BusinessHour::where('year', $year)->where('month', $month)->count() === 0) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }

        $hours = BusinessHour::where('year', $year)
            ->where('month', $month)
            ->orderBy('week_of_month')
            ->orderByRaw("FIELD(day_of_week, '月','火','水','木','金','土','日')")
            ->get();

        return response()->json($hours);
    }

    /**
     * 週単位データを更新
     * PUT /api/business-hours/weekly
     */
    public function updateWeekly(Request $request)
    {
        $records = $request->all();

        foreach ($records as $data) {
            $open_time = $data['is_closed'] ? null : $data['open_time'];
            $close_time = $data['is_closed'] ? null : $data['close_time'];

            BusinessHour::updateOrCreate(
                [
                    'year' => $data['year'],
                    'month' => $data['month'],
                    'week_of_month' => $data['week_of_month'],
                    'day_of_week' => $data['day_of_week'],
                ],
                [
                    'open_time' => $open_time,
                    'close_time' => $close_time,
                    'is_closed' => $data['is_closed'] ?? false,
                ]
            );
        }

        return response()->json(['message' => '週単位の営業時間を更新しました'], 200);
    }
}
