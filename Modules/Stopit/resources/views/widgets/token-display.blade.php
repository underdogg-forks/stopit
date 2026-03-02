<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-key"
                    class="h-5 w-5 text-success-500"
                />
                <span>Your API Token</span>
            </div>
        </x-slot>
        
        <x-slot name="description">
            This token will only be shown once. Make sure to copy it to a secure location.
        </x-slot>

        @if($token)
            <div class="space-y-4">
                <div 
                    x-data="{ 
                        token: @js($token),
                        copied: false,
                        copyToken() {
                            navigator.clipboard.writeText(this.token).then(() => {
                                this.copied = true;
                                setTimeout(() => { this.copied = false; }, 3000);
                            });
                        }
                    }"
                    class="space-y-3"
                >
                    <!-- Token Display -->
                    <div class="relative">
                        <div class="flex items-center gap-2 rounded-lg bg-gray-950/5 dark:bg-white/5 p-4 font-mono text-sm break-all">
                            <code class="flex-1 text-gray-700 dark:text-gray-300">{{ $token }}</code>
                        </div>
                    </div>

                    <!-- Copy Button -->
                    <div class="flex items-center gap-3">
                        <x-filament::button
                            @click="copyToken()"
                            icon="heroicon-o-clipboard-document"
                            color="success"
                            size="sm"
                        >
                            <span x-show="!copied">Copy Token</span>
                            <span x-show="copied" x-cloak class="flex items-center gap-1">
                                <x-filament::icon
                                    icon="heroicon-o-check-circle"
                                    class="h-4 w-4"
                                />
                                Copied!
                            </span>
                        </x-filament::button>
                        
                        <div x-show="copied" x-cloak class="text-sm text-success-600 dark:text-success-400 flex items-center gap-1">
                            <x-filament::icon
                                icon="heroicon-o-check-circle"
                                class="h-4 w-4"
                            />
                            <span>Token copied to clipboard</span>
                        </div>
                    </div>

                    <!-- Usage Example -->
                    <div class="mt-6 space-y-2">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Usage Example
                        </h4>
                        <div class="rounded-lg bg-gray-950/5 dark:bg-white/5 p-4">
                            <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-x-auto"><code>curl -X POST {{ config('app.url') }}/api/v1/exceptions \
  -H "Authorization: Bearer {{ Str::substr($token, 0, 20) }}..." \
  -H "Content-Type: application/json" \
  -d '{
    "exception_class": "RuntimeException",
    "message": "An error occurred",
    "file": "/path/to/file.php",
    "line": 42,
    "severity": "error"
  }'</code></pre>
                        </div>
                    </div>

                    <!-- Security Warning -->
                    <div class="rounded-lg border border-warning-600 bg-warning-50 dark:bg-warning-950/20 p-4">
                        <div class="flex items-start gap-3">
                            <x-filament::icon
                                icon="heroicon-o-exclamation-triangle"
                                class="h-5 w-5 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5"
                            />
                            <div class="text-sm text-warning-800 dark:text-warning-200 space-y-1">
                                <p class="font-semibold">Important Security Information:</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Store this token securely - treat it like a password</li>
                                    <li>Never commit it to version control</li>
                                    <li>This token grants full access to your application's exception reporting</li>
                                    <li>If compromised, regenerate it immediately</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-filament::icon
                    icon="heroicon-o-shield-exclamation"
                    class="h-12 w-12 mx-auto mb-3 text-gray-400"
                />
                <p>No token available to display.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
