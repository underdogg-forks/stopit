<?php

namespace Modules\Stopit\Filament\Resources\ApplicationResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Stopit\DTOs\ApplicationData;
use Modules\Stopit\Filament\Resources\ApplicationResource;
use Modules\Stopit\Services\ApplicationService;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Override account_id with current user's first account for security
        $data['account_id'] = auth()->user()->accounts()->first()->id;
        
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $applicationData = new ApplicationData();
        $applicationData->setAccountId($data['account_id'])
            ->setName($data['name'])
            ->setSlug($data['slug']);

        $service = app(ApplicationService::class);
        $result = $service->createApplication($applicationData);

        Notification::make()
            ->title('Application Created')
            ->body("API Token (save this, it won't be shown again): {$result['plain_token']}")
            ->success()
            ->persistent()
            ->send();

        return $result['application'];
    }

}
