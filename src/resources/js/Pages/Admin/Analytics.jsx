import React, { useState, useEffect, useCallback } from 'react';
// Lucide Reactからアイコンをインポート
import { TrendingUp, Users, CalendarDays, Percent, ArrowUp, ArrowDown } from 'lucide-react';

// ダッシュボードと同じレイアウト構造を再利用
const AuthenticatedLayout = ({ user, header, children }) => (
    <div className="min-h-screen bg-gray-100">
        <header className="bg-white shadow">
            <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {header}
            </div>
        </header>
        <main className="py-12">{children}</main>
    </div>
);
const Head = ({ title }) => <title>{title}</title>;

// --- モックデータとシミュレーションロジック ---

// 1. KPIサマリーデータ (今月 vs 先月)
const mockKpis = {
    reservations: { value: 150, change: 15, unit: '件', label: '予約総数', icon: CalendarDays, color: 'text-indigo-600', trend: 'up' },
    newClients: { value: 25, change: 5, unit: '人', label: '新規顧客数', icon: Users, color: 'text-orange-600', trend: 'up' },
    cancellationRate: { value: 5.0, change: -1.0, unit: '%', label: 'キャンセル率', icon: Percent, color: 'text-red-600', trend: 'down' },
    averageRevenue: { value: 7800, change: -200, unit: '円', label: '客単価', icon: '¥', color: 'text-green-600', trend: 'down' },
};

// 2. 月次予約推移データ (過去6ヶ月)
const mockMonthlyData = [
    { month: '5月', count: 75 },
    { month: '6月', count: 80 },
    { month: '7月', count: 95 },
    { month: '8月', count: 110 },
    { month: '9月', count: 135 },
    { month: '10月', count: 150 }, // 最新月
];
const maxMonthlyCount = Math.max(...mockMonthlyData.map(d => d.count));

// 3. サービス別人気度データ
const mockServicePopularity = [
    { service: 'シングルラッシュ', count: 60, color: 'bg-indigo-500' },
    { service: 'ボリュームラッシュ', count: 40, color: 'bg-orange-500' },
    { service: 'ラッシュリフト', count: 30, color: 'bg-pink-500' },
    { service: 'アイブロウWAX', count: 20, color: 'bg-teal-500' },
];
const maxServiceCount = Math.max(...mockServicePopularity.map(d => d.count));

// --- ヘルパーコンポーネント ---

/**
 * KPIサマリーカード
 */
const KPICard = ({ data }) => {
    const { value, change, unit, label, icon: Icon, color, trend } = data;
    const isUp = trend === 'up';
    const trendColor = isUp ? 'text-green-500 bg-green-100' : 'text-red-500 bg-red-100';
    const TrendIcon = isUp ? ArrowUp : ArrowDown;
    const formattedChange = Math.abs(change) + unit;

    return (
        <div className="bg-white p-6 rounded-xl shadow-lg border border-gray-100 transition duration-300 hover:shadow-xl">
            <div className="flex justify-between items-start">
                <div>
                    <p className="text-sm font-medium text-gray-500">{label}</p>
                    <div className="mt-1 text-4xl font-extrabold text-gray-900 flex items-baseline">
                        {label === '客単価' && <span className="text-xl mr-1 self-end">¥</span>}
                        {value}
                        <span className="text-xl ml-1 text-gray-500">{unit}</span>
                    </div>
                </div>
                {typeof Icon === 'function' ? (
                    <Icon className={`w-10 h-10 p-2 rounded-full opacity-80 ${color} bg-opacity-10`} />
                ) : (
                    <div className={`w-10 h-10 p-2 rounded-full opacity-80 ${color} bg-opacity-10 text-xl font-bold flex items-center justify-center`}>
                        {Icon}
                    </div>
                )}
            </div>
            <div className="mt-4 flex items-center">
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${trendColor}`}>
                    <TrendIcon className="w-4 h-4 mr-1" />
                    {formattedChange}
                </span>
                <span className="ml-3 text-sm text-gray-500">
                    先月比
                </span>
            </div>
        </div>
    );
};


/**
 * 簡易棒グラフ (サービス人気度)
 */
const ServicePopularityChart = ({ data, maxCount }) => (
    <div className="space-y-4">
        {data.map((item, index) => {
            // 最大値に対する割合を計算
            const percentage = (item.count / maxCount) * 100;
            const widthStyle = { width: `${percentage}%` };

            return (
                <div key={index} className="flex items-center space-x-4">
                    <p className="w-32 text-sm font-medium text-gray-700 truncate">{item.service}</p>
                    <div className="flex-grow bg-gray-200 rounded-full h-8 overflow-hidden">
                        <div
                            className={`h-full flex items-center justify-end px-3 rounded-full transition-all duration-700 ease-out ${item.color}`}
                            style={widthStyle}
                        >
                            <span className="text-white text-xs font-bold">{item.count}件</span>
                        </div>
                    </div>
                </div>
            );
        })}
    </div>
);

/**
 * 簡易折れ線グラフ (月次予約推移)
 */
const MonthlyTrendChart = ({ data, maxCount }) => {
    // データポイント間のパスを計算（SVGで簡易的なライングラフをシミュレート）
    // スケールを0から100に正規化
    const normalizedData = data.map(d => ({
        ...d,
        normalized: (d.count / maxCount) * 100
    }));

    // SVG path 'd' attribute の計算 (ここでは簡略化のためCSS表現のみ)
    // 実際にはSVGを使うが、ここではグリッドとマーカーのみで視覚化
    return (
        <div className="relative h-64 w-full pt-4">
            <div className="absolute inset-0 border-l border-b border-gray-300 grid grid-cols-6 grid-rows-4 gap-0">
                {/* 水平グリッドライン */}
                {[...Array(3)].map((_, i) => (
                    <div key={`h-${i}`} className="col-span-6 border-t border-gray-200" style={{ height: '25%' }}></div>
                ))}
            </div>

            <div className="absolute inset-0 flex justify-around items-end pb-1 text-xs font-medium text-gray-500">
                {normalizedData.map((d, index) => (
                    <div key={d.month} className="flex flex-col items-center w-full relative">
                        {/* データポイントと数値 */}
                        <div
                            className={`absolute -top-1.5 w-3 h-3 rounded-full bg-indigo-500 shadow-lg ring-4 ring-white transition duration-500`}
                            style={{ bottom: `${d.normalized}%` }}
                            title={`${d.month}: ${d.count}件`}
                        ></div>
                        <span className="absolute text-sm font-bold text-gray-900" style={{ bottom: `${d.normalized}%`, transform: 'translateY(-150%)' }}>
                            {d.count}
                        </span>

                        {/* 月ラベル */}
                        <span className="mt-auto">{d.month}</span>
                    </div>
                ))}
            </div>
            {/* 凡例 (最大値) */}
            <div className="absolute top-0 left-0 text-sm font-semibold text-gray-700">
                {maxCount}
            </div>
            {/* 凡例 (ゼロ) */}
            <div className="absolute bottom-0 -left-6 text-sm font-semibold text-gray-700">
                0
            </div>
        </div>
    );
};


/**
 * アナリティクス メインコンポーネント
 */
export default function Analytics({ auth }) {
    const [loading, setLoading] = useState(true);
    const [kpis, setKpis] = useState({});
    const [monthlyData, setMonthlyData] = useState([]);
    const [serviceData, setServiceData] = useState([]);

    // データ読み込みのシミュレーション
    useEffect(() => {
        setLoading(true);
        // 実際はここでAPIコール
        setTimeout(() => {
            setKpis(mockKpis);
            setMonthlyData(mockMonthlyData);
            setServiceData(mockServicePopularity);
            setLoading(false);
        }, 800);
    }, []);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="text-3xl font-extrabold text-gray-900 flex items-center">
                    <TrendingUp className="w-8 h-8 mr-3 text-green-600" />
                    全体概要 (アナリティクス)
                </h2>
            }
        >
            <Head title="全体概要" />

            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="space-y-10">

                    {/* 1. KPI サマリーカード */}
                    <section>
                        <h3 className="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">今月の主要業績指標 (KPI)</h3>
                        {loading ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                {[...Array(4)].map((_, i) => (
                                    <div key={i} className="bg-gray-200 p-6 rounded-xl shadow-lg h-32 animate-pulse"></div>
                                ))}
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                {Object.keys(kpis).map(key => (
                                    <KPICard key={key} data={kpis[key]} />
                                ))}
                            </div>
                        )}
                    </section>

                    {/* 2. 月次予約の推移とサービス別人気度 */}
                    <section className="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        {/* 予約数推移グラフ (2/3幅) */}
                        <div className="lg:col-span-2 bg-white p-6 sm:p-8 rounded-2xl shadow-xl border border-gray-200">
                            <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
                                <CalendarDays className="w-5 h-5 mr-2 text-indigo-500" />
                                月次予約件数の推移 (過去6ヶ月)
                            </h3>
                            {loading ? (
                                <div className="h-64 flex items-center justify-center text-gray-500">グラフを読み込み中...</div>
                            ) : (
                                <MonthlyTrendChart data={monthlyData} maxCount={maxMonthlyCount} />
                            )}
                        </div>

                        {/* サービス別人気度グラフ (1/3幅) */}
                        <div className="lg:col-span-1 bg-white p-6 sm:p-8 rounded-2xl shadow-xl border border-gray-200">
                            <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
                                <TrendingUp className="w-5 h-5 mr-2 text-green-500" />
                                人気サービスランキング
                            </h3>
                            {loading ? (
                                <div className="h-64 flex items-center justify-center text-gray-500">ランキングを読み込み中...</div>
                            ) : (
                                <ServicePopularityChart data={serviceData} maxCount={maxServiceCount} />
                            )}
                        </div>

                    </section>

                    {/* 3. その他アナリティクス（例: 会員ランク別構成比など） */}
                    <section className="bg-white p-6 sm:p-8 rounded-2xl shadow-xl border border-gray-200">
                        <h3 className="text-xl font-bold text-gray-800 mb-4">顧客構成分析</h3>
                        <p className="text-gray-600">
                            ここでは、顧客の年齢層、リピート回数、会員ランクなどのより詳細な分析データが表示されます。（実装予定）
                        </p>
                    </section>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
