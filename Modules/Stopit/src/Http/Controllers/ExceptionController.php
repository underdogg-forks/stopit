<?php

namespace Stopit\src\Providers\src\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Stopit\src\Providers\Services\ExceptionCollectionService;
use Stopit\src\Providers\src\Http\Requests\StoreExceptionRequest;
use Stopit\src\Providers\src\Transformers\ExceptionTransformer;

class ExceptionController extends Controller
{
    public function __construct(
        private ExceptionCollectionService $service,
        private ExceptionTransformer $transformer
    ) {}

    public function store(StoreExceptionRequest $request): JsonResponse
    {
        $application = $request->attributes->get('application');

        $data = $this->transformer->fromRequest($request);

        $exception = $this->service->reportException($application->id, $data);

        return response()->json([
            'id'              => $exception->id,
            'exception_class' => $exception->exception_class,
            'message'         => $exception->message,
            'created_at'      => $exception->created_at->toIso8601String(),
        ], 201);
    }
}
