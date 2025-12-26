import React from "react";
import { usePage, router } from "@inertiajs/react";

export default function ReservationHistory() {
    const { reservations, flash } = usePage().props;

    const handleCancel = (id) => {
        if (confirm("この予約をキャンセルしますか？")) {
            router.delete(route("user.reservations.cancel", id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="max-w-4xl mx-auto py-10 px-4">
            <h1 className="text-2xl font-bold mb-6 text-gray-800">予約履歴</h1>

            {flash?.message && (
                <div className="mb-4 p-3 bg-green-100 text-green-700 rounded-lg">
                    {flash.message}
                </div>
            )}

            {reservations.length === 0 ? (
                <p className="text-gray-500">まだ予約履歴はありません。</p>
            ) : (
                <table className="min-w-full border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="py-3 px-4 text-left text-sm font-semibold text-gray-600">
                                メニュー
                            </th>
                            <th className="py-3 px-4 text-left text-sm font-semibold text-gray-600">
                                日付
                            </th>
                            <th className="py-3 px-4 text-left text-sm font-semibold text-gray-600">
                                時間
                            </th>
                            <th className="py-3 px-4 text-left text-sm font-semibold text-gray-600">
                                状態
                            </th>
                            <th className="py-3 px-4 text-center text-sm font-semibold text-gray-600">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {reservations.map((r) => (
                            <tr
                                key={r.id}
                                className="border-t hover:bg-gray-50 transition"
                            >
                                <td className="py-3 px-4">{r.service_name}</td>
                                <td className="py-3 px-4">{r.date}</td>
                                <td className="py-3 px-4">{r.time}</td>
                                <td className="py-3 px-4">
                                    <span
                                        className={`${r.status === "確定"
                                                ? "text-green-600"
                                                : r.status === "キャンセル"
                                                    ? "text-gray-400"
                                                    : "text-yellow-600"
                                            } font-semibold`}
                                    >
                                        {r.status}
                                    </span>
                                </td>
                                <td className="py-3 px-4 text-center">
                                    {r.status === "確定" ? (
                                        <button
                                            onClick={() => handleCancel(r.id)}
                                            className="px-3 py-1 text-sm text-red-600 border border-red-500 rounded-lg hover:bg-red-50 transition"
                                        >
                                            キャンセル
                                        </button>
                                    ) : (
                                        <span className="text-gray-400 text-sm">
                                            -
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
