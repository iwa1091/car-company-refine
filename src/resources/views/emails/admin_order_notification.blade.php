{{-- resources/views/emails/admin_order_notification.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>【通知】新しい注文がありました | LASH BROW OHANA</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Arial', 'Hiragino Sans', 'Meiryo', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 40px 0;
        }
        .container {
            max-width: 640px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 40px 30px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }
        h1 {
            color: #1a202c;
            font-size: 22px;
            margin-bottom: 15px;
        }
        .order-info {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 20px;
            font-size: 14px;
        }
        .order-info h2 {
            font-size: 16px;
            color: #444;
            margin-bottom: 10px;
            border-left: 4px solid #c0392b;
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        td {
            padding: 8px 4px;
            border-bottom: 1px solid #eee;
        }
        .customer {
            margin-top: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 30px;
        }
        .alert {
            background: #e74c3c;
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert">🔔 新しい注文が届きました</div>

        <h1>注文情報</h1>

        <div class="order-info">
            <h2>注文内容</h2>
            <table>
                <tr>
                    <td>注文番号</td>
                    <td>{{ $order->order_number }}</td>
                </tr>
                <tr>
                    <td>商品名</td>
                    <td>{{ $order->product->name ?? '商品情報なし' }}</td>
                </tr>
                <tr>
                    <td>金額</td>
                    <td>¥{{ number_format($order->amount) }}（税込）</td>
                </tr>
                <tr>
                    <td>決済ステータス</td>
                    <td>{{ ucfirst($order->payment_status) }}</td>
                </tr>
                <tr>
                    <td>Stripe セッションID</td>
                    <td>{{ $order->stripe_session_id }}</td>
                </tr>
                <tr>
                    <td>注文日時</td>
                    <td>{{ $order->ordered_at->format('Y年m月d日 H:i') }}</td>
                </tr>
            </table>

            <div class="customer">
                <strong>顧客情報：</strong><br>
                氏名：{{ $order->shipping_name ?? '未登録' }}<br>
                住所：{{ $order->shipping_address ?? '住所未入力' }}<br>
                電話番号：{{ $order->shipping_phone ?? '不明' }}<br>
                メール：{{ $order->user->email ?? '未登録' }}
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} LASH BROW OHANA 管理システム
        </div>
    </div>
</body>
</html>
