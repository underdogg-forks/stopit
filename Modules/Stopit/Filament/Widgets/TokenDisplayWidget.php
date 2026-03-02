<?php

namespace Modules\Stopit\Filament\Widgets;

use Filament\Widgets\Widget;

class TokenDisplayWidget extends Widget
{
    protected static string $view = 'stopit::widgets.token-display';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $token = null;
    
    public function mount(): void
    {
        // Get the token from the parent page
        $livewire = $this->getLivewire();
        
        if (method_exists($livewire, 'getRevealedToken')) {
            $this->token = $livewire->getRevealedToken();
        }
    }
}
