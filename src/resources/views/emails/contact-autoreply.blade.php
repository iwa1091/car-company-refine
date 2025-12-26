<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>お問い合わせありがとうございます</title>
    <style>
        body {
            font-family: Arial, 'Hiragino Sans', Meiryo, sans-serif;
            background: #f8f8f8;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 640px;
            margin: auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        h2 {
            font-size: 20px;
            margin-top: 0;
            border-left: 4px solid #CDAF63;
            padding-left: 10px;
        }
        p {
            line-height: 1.7;
            font-size: 15px;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>お問い合わせありがとうございます</h2>

    <p>{{ $data['name'] }} 様</p>

    <p>
        この度は <strong>Lash Brow Ohana</strong> へお問い合わせいただき誠にありがとうございます。<br>
        内容を確認し、2営業日以内にご返信いたします。
    </p>

    <p><strong>【お客様のお問い合わせ内容】</strong></p>
    <p>
        件名：{{ $data['subject'] }}<br>
        内容：<br>
        {!! nl2br(e($data['message'])) !!}
    </p>

    <p class="footer">
        &copy; {{ date('Y') }} Lash Brow Ohana  
    </p>
</div>

</body>
</html>
