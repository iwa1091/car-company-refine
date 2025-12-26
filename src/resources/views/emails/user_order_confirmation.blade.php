{{-- resources/views/emails/user_order_confirmation.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ご購入ありがとうございます | LASH BROW OHANA</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Arial', 'Hiragino Sans', 'Meiryo', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 40px 0;
        }
        .container {
            max-width: 640px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        h1 {
            color: #2c3e50;
            font-size: 22px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .greeting {
            font-size: 15px;
            margin-top: 10px;
            line-height: 1.8;
        }
        .order-info {
            background: #f6f8fa;
            border-radius: 6px;
            padding: 20px;
            margin-top: 25px;
            font-size: 14px;
        }
        .order-info h2 {
            font-size: 16px;
            color: #444;
            margin-bottom: 12px;
            border-left: 4px solid #8a6d3b;
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
            font-size: 14px;
        }
        .thanks {
            margin-top: 30px;
            font-size: 15px;
            line-height: 1.8;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            width: 140px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('img/logo.png') }}" alt="LASH BROW OHANA">
        </div>

        <h1>ご購入ありがとうございます</h1>

        <p class="greeting">
            {{ $order->shipping_name }} 様<br>
            この度は <strong>LASH BROW OHANA</strong> オンラインストアをご利用いただき、誠にありがとうございます。<br>
            以下の内容でご注文を承りました。
        </p>

        <div class="order-info">
            <h2>ご注文内容</h2>
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
                    <td>お支払い状況</td>
                    <td>{{ ucfirst($order->payment_status) }}</td>
                </tr>
                <tr>
                    <td>ご注文日時</td>
                    <td>{{ $order->ordered_at->format('Y年m月d日 H:i') }}</td>
                </tr>
            </table>
        </div>

        <p class="thanks">
            商品の準備が整い次第、改めて発送のご案内をお送りいたします。<br>
            今後とも <strong>LASH BROW OHANA</strong> をよろしくお願いいたします。
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} LASH BROW OHANA<br>
            市原市 — メディカルエステ認定サロン
        </div>
    </div>
</body>
</html>
