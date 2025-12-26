<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
// 💡 変更: AdminScheduleからScheduleモデルに変更
use App\Models\Schedule; 
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * 管理者向けの営業時間と例外日設定を管理するコントローラーです。
 * Scheduleモデルの 'weekly' および 'exception' タイプの設定を扱います。
 */
class ScheduleController extends Controller
{
    /**
     * 管理者スケジュール設定画面を表示します。
     */
    public function index()
    {
        // 1. 曜日ごとの基本スケジュールを取得 (有効期間内のもの)
        // ここでは、現在日付を基準に最も新しい週次設定を取得することを想定
        $weeklySchedules = Schedule::weekly(Carbon::now())
                                    ->where('type', 'weekly')
                                    ->get()
                                    ->keyBy('day_of_week'); // day_of_week (0-6)をキーとする
        
        // 2. 今後の例外スケジュールを取得 (今日以降のもの)
        $exceptionSchedules = Schedule::where('type', 'exception')
                                    ->where('date', '>=', Carbon::now()->toDateString())
                                    ->orderBy('date', 'asc')
                                    ->get();

        // InertiaでAdmin/Schedule.jsxコンポーネントを表示
        return Inertia::render('Admin/Schedule', [
            // 曜日ごとの設定 (0:日, 1:月, ...)
            'weeklySchedules' => $weeklySchedules->toArray(),
            // 特定日の例外設定 (日付、開始時間、終了時間)
            'exceptionSchedules' => $exceptionSchedules->map(function ($schedule) {
                return [
                    'date' => $schedule->date->format('Y-m-d'),
                    'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : null,
                    'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : null,
                    'is_holiday' => is_null($schedule->start_time) && is_null($schedule->end_time),
                ];
            })->toArray(),
        ]);
    }

    /**
     * 特定の日付のスケジュールデータを取得します。（月移動時など、クライアントからのAPIリクエスト用）
     * 💡 このメソッドはReact側でカレンダー移動時に呼び出されることを想定しています。
     */
    public function getData(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $targetDate = Carbon::parse($request->date);
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();
        
        // 当月の例外スケジュールのみを返す
        $exceptionSchedules = Schedule::where('type', 'exception')
                                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                                    ->get();
        
        return response()->json([
            'exceptionSchedules' => $exceptionSchedules->map(function ($schedule) {
                return [
                    'date' => $schedule->date->format('Y-m-d'),
                    'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : null,
                    'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : null,
                    'is_holiday' => is_null($schedule->start_time) && is_null($schedule->end_time),
                ];
            })->toArray(),
        ]);
    }


    /**
     * 曜日ごとの基本スケジュール設定（Weekly）を保存または更新します。
     * day_of_week, start_time, end_time を受け取ります。
     */
    public function storeOrUpdateWeekly(Request $request)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['nullable', 'date_format:H:i'],
            // start_timeがある場合のみ、afterチェックを行う
            'end_time' => ['nullable', 'date_format:H:i', Rule::when($request->filled('start_time'), ['after:start_time'])],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // 既存の基本設定を特定し、更新または新規作成
        // 💡 既存のweekly設定を検索し、存在すれば更新、なければ新規作成します。
        $schedule = Schedule::weekly(Carbon::now())
                             ->where('day_of_week', $request->day_of_week)
                             ->first();

        if ($schedule) {
            $schedule->update([
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);
        } else {
             // 新規作成時（effective_from はデフォルトで現在日に設定）
            Schedule::create([
                'type' => 'weekly',
                'day_of_week' => $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);
        }


        return redirect()->back()->with('success', '曜日別スケジュールが正常に保存されました。');
    }

    /**
     * 特定の日付の例外スケジュール設定（Exception）を保存または更新します。
     * date, start_time, end_time, is_holiday を受け取ります。
     */
    public function storeOrUpdateException(Request $request)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
            'is_holiday' => ['boolean'], // 休業日として設定するかどうか
            'start_time' => ['nullable', 'date_format:H:i', Rule::requiredIf(!$request->input('is_holiday'))],
            // start_timeがある場合のみ、afterチェックを行う
            'end_time' => ['nullable', 'date_format:H:i', Rule::when($request->filled('start_time'), ['after:start_time'])],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // 休業日としてマークする場合、start_timeとend_timeをnullにする
        $startTime = $request->input('is_holiday') ? null : $request->start_time;
        $endTime = $request->input('is_holiday') ? null : $request->end_time;


        // データの保存または更新
        // 特定日に対する例外設定は、重複しないように updateOrCreate を使用
        Schedule::updateOrCreate(
            [
                'type' => 'exception', 
                'date' => $request->date
            ],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]
        );

        return redirect()->back()->with('success', '特定日の例外スケジュールが正常に保存されました。');
    }

    /**
     * スケジュールを削除します（特定日の例外設定の削除のみ）。
     * 週次設定は削除ではなく、時間をnullにする更新で対応します。
     */
    public function destroyException(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        // 'exception' タイプで、指定された日付のレコードを削除
        Schedule::where('type', 'exception')
                ->where('date', $request->date)
                ->delete();

        return redirect()->back()->with('success', '該当日に対する例外設定が削除されました。基本スケジュール設定に戻ります。');
    }

    // 以前の fetchSchedulesForMonth, storeOrUpdate, destroy は廃止（上記の新メソッドに置き換え）

}
