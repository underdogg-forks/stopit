<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Not Found - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #2d3748;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
        }
        
        .error-code {
            font-size: 72px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        p {
            color: #718096;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <h1>Workspace Not Found</h1>
        <p>
            The workspace you're trying to access doesn't exist or you don't have permission to view it.
            Please check the URL and try again.
        </p>
        <a href="{{ config('app.url') }}" class="btn">Go to Main Site</a>
    </div>
</body>
</html>
