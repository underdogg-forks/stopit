<?php

namespace Modules\Stopit\Transformers;

use Illuminate\Http\Request;
use Modules\Stopit\DTOs\ExceptionData;

class ExceptionTransformer
{
    public function fromRequest(Request $request): ExceptionData
    {
        $data = new ExceptionData();

        $data->setExceptionClass($request->input('exception_class'))
            ->setMessage($request->input('message'))
            ->setFile($request->input('file'))
            ->setLine($request->input('line'))
            ->setStackTrace($request->input('stack_trace'))
            ->setRequestMethod($request->input('request_method'))
            ->setRequestUrl($request->input('request_url'))
            ->setHeaders($request->input('headers'))
            ->setUserAgent($request->input('user_agent'))
            ->setIpAddress($request->input('ip_address'))
            ->setUserId($request->input('user_id'))
            ->setContext($request->input('context'))
            ->setSeverity($request->input('severity', 'error'));

        return $data;
    }
}
