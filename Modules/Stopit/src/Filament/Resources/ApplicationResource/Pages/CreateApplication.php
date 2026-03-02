<?php

namespace Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Stopit\src\Providers\Services\ApplicationService;
use Stopit\src\Providers\src\DTOs\ApplicationData;
use Stopit\src\Providers\src\Filament\Resources\ApplicationResource;
use RuntimeException;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Override account_id with current user's first account for security
        $user = auth()->user();

        if ( ! $user) {
            throw new RuntimeException('User must be authenticated to create an application.');
        }

        $firstAccount = $user->accounts()->orderBy('id')->first();

        if ( ! $firstAccount) {
            throw new RuntimeException('User must belong to at least one account to create an application.');
        }

        $data['account_id'] = $firstAccount->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $applicationData = new ApplicationData();
        $applicationData->setAccountId($data['account_id'])
            ->setName($data['name'])
            ->setSlug($data['slug']);

        $service = app(ApplicationService::class);
        $result  = $service->createApplication($applicationData);

        // Store the token in session to display it on the view page
        session()->flash('revealed_token', $result['plain_token']);

        Notification::make()
            ->title('Application Created Successfully')
            ->body('Your API token is displayed on the next page. Make sure to copy it to a secure location.')
            ->success()
            ->duration(8000)
            ->send();

        return $result['application'];
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the view page to show the token
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
