<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 w-full max-w-md">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-gray-900">POS System Login</h1>
            <p class="text-gray-600 mt-2">Sign in to access your dashboard</p>
        </div>
        <form action="authenticate.php" method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username or Email</label>
                <div class="mt-1">
                    <input id="username" name="username" type="text" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                           placeholder="Enter your username or email">
                </div>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                           placeholder="Enter your password">
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox"
                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                </div>
                <div class="text-sm">
                    <a href="#" class="font-medium text-emerald-600 hover:text-emerald-500">Forgot password?</a>
                </div>
            </div>
            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                    Sign in
                </button>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Don't have an account? <a href="signup.php" class="font-medium text-emerald-600 hover:text-emerald-500">Sign up</a></p>
            </div>
        </form>
        <?php if (isset($_GET['error'])): ?>
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600"></i>
                    <span class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-green-600"></i>
                    <span class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($_GET['success']); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>