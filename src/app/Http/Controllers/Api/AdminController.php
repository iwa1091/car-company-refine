<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 管理者向けのサービスおよびスケジュール設定APIを管理するコントローラー
 */
class AdminController extends Controller
{
    // --- サービス (Service) 管理 ---

    /**
     * 全サービスを取得
     */
    public function indexServices()
    {
        // 表示順とアクティブな状態を考慮してサービスを取得
        $services = Service::orderBy('sort_order')->get();
        return response()->json($services);
    }

    /**
     * 新しいサービスを登録
     */
    public function storeService(Request $request)
    {
        // nameはユニークである必要があります
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services,name',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = Service::create($request->all());
        return response()->json($service, 201);
    }

    /**
     * 既存のサービスを更新
     */
    public function updateService(Request $request, Service $service)
    {
        $validator = Validator::make($request->all(), [
            // nameのユニークチェック時、自分自身は除外する
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('services')->ignore($service->id)],
            'duration_minutes' => 'sometimes|required|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update($request->all());
        return response()->json($service);
    }

    /**
     * サービスを削除
     */
    public function destroyService(Service $service)
    {
        // 実際には、関連する予約の有無を確認するロジックが必要です
        $service->delete();
        return response()->json(['message' => 'サービスが削除されました。'], 200);
    }

    // --- スケジュール (Schedule) 管理 ---

    /**
     * 全スケジュール設定を取得
     */
    public function indexSchedules()
    {
        // 有効期間順で取得
        $schedules = Schedule::orderBy('effective_from', 'desc')->get();
        return response()->json($schedules);
    }

    /**
     * 新しいスケジュール設定を登録
     */
    public function storeSchedule(Request $request)
    {
        $rules = [
            'type' => 'required|in:weekly,exception',
            // 時間のバリデーション: 24時間表記 (例: 10:00)
            'start_time' => 'nullable|date_format:H:i',
            // end_timeが存在し、かつstart_timeが存在する場合、start_timeより後であること
            'end_time' => 'nullable|date_format:H:i|after:start_time', 
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ];

        // タイプ別の必須項目チェック
        if ($request->input('type') === 'weekly') {
            $rules['day_of_week'] = 'required|integer|between:0,6'; // 曜日
            $rules['date'] = 'nullable';
        } else { // exception (特定日)
            $rules['date'] = 'required|date';
            $rules['day_of_week'] = 'nullable';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule = Schedule::create($request->all());
        return response()->json($schedule, 201);
    }

    /**
     * 既存のスケジュール設定を更新
     */
    public function updateSchedule(Request $request, Schedule $schedule)
    {
        $rules = [
            'type' => 'sometimes|required|in:weekly,exception',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'effective_from' => 'sometimes|required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ];

        // 更新時もtypeに基づいたバリデーションルールを適用
        if ($request->input('type') === 'weekly') {
            $rules['day_of_week'] = 'sometimes|required|integer|between:0,6';
        } elseif ($request->input('type') === 'exception') {
            $rules['date'] = 'sometimes|required|date';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule->update($request->all());
        return response()->json($schedule);
    }

    /**
     * スケジュール設定を削除
     */
    public function destroySchedule(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json(['message' => 'スケジュール設定が削除されました。'], 200);
    }
}
