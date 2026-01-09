<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
// use App\Models\Schedule; // ✅ Schedule モデルが存在しない環境で fatal になるため削除
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    // ✅ Schedule モデルが無い環境でも落ちないように、DB クエリで扱うように修正

    /**
     * 全スケジュール設定を取得
     */
    public function indexSchedules()
    {
        if (!Schema::hasTable('schedules')) {
            return response()->json([
                'message' => 'スケジュール機能が利用できません（schedules テーブルが見つかりません）。',
                'data' => [],
            ], 200);
        }

        // 有効期間順で取得
        $schedules = DB::table('schedules')->orderBy('effective_from', 'desc')->get();
        return response()->json($schedules);
    }

    /**
     * 新しいスケジュール設定を登録
     */
    public function storeSchedule(Request $request)
    {
        if (!Schema::hasTable('schedules')) {
            return response()->json([
                'message' => 'スケジュール機能が利用できません（schedules テーブルが見つかりません）。',
            ], 501);
        }

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

        // schedules テーブルに入れる想定のカラムのみ拾う（不明カラム混入を防ぐ）
        $data = $request->only([
            'type',
            'day_of_week',
            'date',
            'start_time',
            'end_time',
            'effective_from',
            'effective_to',
        ]);

        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('schedules')->insertGetId($data);

        $schedule = DB::table('schedules')->where('id', $id)->first();
        return response()->json($schedule, 201);
    }

    /**
     * 既存のスケジュール設定を更新
     *
     * ※ 以前は Schedule $schedule でルートモデルバインドしていたが、
     *    Schedule モデルが無い環境で fatal になるため、ID で受け取る
     */
    public function updateSchedule(Request $request, $schedule)
    {
        if (!Schema::hasTable('schedules')) {
            return response()->json([
                'message' => 'スケジュール機能が利用できません（schedules テーブルが見つかりません）。',
            ], 501);
        }

        // ルートパラメータが {schedule} の場合は通常文字列/数値で入る
        $scheduleId = is_object($schedule) && isset($schedule->id) ? $schedule->id : $schedule;

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

        $exists = DB::table('schedules')->where('id', $scheduleId)->exists();
        if (!$exists) {
            return response()->json(['message' => 'スケジュールが見つかりません。'], 404);
        }

        $data = $request->only([
            'type',
            'day_of_week',
            'date',
            'start_time',
            'end_time',
            'effective_from',
            'effective_to',
        ]);
        $data['updated_at'] = now();

        DB::table('schedules')->where('id', $scheduleId)->update($data);

        $updated = DB::table('schedules')->where('id', $scheduleId)->first();
        return response()->json($updated);
    }

    /**
     * スケジュール設定を削除
     *
     * ※ 以前は Schedule $schedule でルートモデルバインドしていたが、
     *    Schedule モデルが無い環境で fatal になるため、ID で受け取る
     */
    public function destroySchedule($schedule)
    {
        if (!Schema::hasTable('schedules')) {
            return response()->json([
                'message' => 'スケジュール機能が利用できません（schedules テーブルが見つかりません）。',
            ], 501);
        }

        $scheduleId = is_object($schedule) && isset($schedule->id) ? $schedule->id : $schedule;

        $exists = DB::table('schedules')->where('id', $scheduleId)->exists();
        if (!$exists) {
            return response()->json(['message' => 'スケジュールが見つかりません。'], 404);
        }

        DB::table('schedules')->where('id', $scheduleId)->delete();
        return response()->json(['message' => 'スケジュール設定が削除されました。'], 200);
    }
}
