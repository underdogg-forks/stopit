<?php

namespace Stopit\src\Providers\src\Filament\Widgets;

use Filament\Widgets\Widget;

class TokenDisplayWidget extends Widget
{
    public ?string $token = null;

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        // Get the token from the parent page
        $livewire = $this->getLivewire();

        if (method_exists($livewire, 'getRevealedToken')) {
            $this->token = $livewire->getRevealedToken();
        }
    }
}
