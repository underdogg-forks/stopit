<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Switch Workspace - {{ config('app.name') }}</title>
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
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 12px;
        }
        
        .subtitle {
            color: #718096;
            margin-bottom: 32px;
            font-size: 16px;
        }
        
        .workspace-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .workspace-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .workspace-card:hover {
            border-color: #667eea;
            background: #f7fafc;
            transform: translateY(-2px);
        }
        
        .workspace-info {
            flex: 1;
        }
        
        .workspace-name {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .workspace-domain {
            font-size: 14px;
            color: #718096;
            font-family: 'Monaco', 'Menlo', monospace;
        }
        
        .workspace-arrow {
            color: #cbd5e0;
            font-size: 24px;
        }
        
        .workspace-card:hover .workspace-arrow {
            color: #667eea;
        }
        
        .current-badge {
            background: #48bb78;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 24px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Switch Workspace</h1>
        <p class="subtitle">Choose a workspace to continue</p>
        
        <div class="workspace-list">
            @forelse($accounts as $account)
                <a href="{{ route('tenant.switch', $account->id) }}" class="workspace-card">
                    <div class="workspace-info">
                        <div class="workspace-name">
                            @if($account->domain && str_contains($currentDomain, $account->domain))
                                <span class="current-badge">Current</span>
                            @endif
                            {{ $account->name }}
                        </div>
                        @if($account->domain)
                            <div class="workspace-domain">{{ $account->domain }}.{{ config('app.base_domain', 'stopit.dev') }}</div>
                        @else
                            <div class="workspace-domain">No domain configured</div>
                        @endif
                    </div>
                    <div class="workspace-arrow">→</div>
                </a>
            @empty
                <p style="color: #718096; text-align: center; padding: 20px;">
                    You don't have access to any workspaces yet.
                </p>
            @endforelse
        </div>
        
        <a href="{{ route('filament.admin.pages.dashboard') }}" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
