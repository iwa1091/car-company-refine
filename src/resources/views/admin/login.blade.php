<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7fafc; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-2xl border border-gray-100">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-6">
            管理者ログイン
        </h2>
        
        <form method="POST" action="{{ route('admin.login') }}" class="space-y-6">
            @csrf
            
            <!-- メールアドレス -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    メールアドレス
                </label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" required 
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150">
                </div>
            </div>
            
            <!-- パスワード -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    パスワード
                </label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" required 
                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150">
                </div>
            </div>
            
            <!-- ログインボタン -->
            <div>
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-lg font-medium text-white bg-pink-500 hover:bg-pink-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition duration-200">
                    ログイン
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                一般ユーザーの方は <a href="/login" class="font-medium text-indigo-600 hover:text-indigo-500">こちら</a>
            </p>
        </div>
    </div>
</body>
</html>
