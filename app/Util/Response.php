<?php

namespace App\Util;

use Illuminate\Http\JsonResponse;

class Response
{
    public const SUCCESS = 'success';
    public const FAILURE = 'failure';

    private string $status;
    private int $statusCode;
    private string $message;
    private array $result;

    public static function init(): self
    {
        $response = new self();
        $response->success();
        return $response;
    }

    public function success(): self
    {
        $this->status = self::SUCCESS;
        return $this;
    }

    public function failure(): self
    {
        $this->status = self::FAILURE;
        return $this;
    }


    public function created(): self
    {
        $this->statusCode = 201;
        return $this;
    }

    public function badRequest(): self
    {
        $this->statusCode = 400;
        return $this;
    }


    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function result(array $result): self
    {
        $this->result = $result;
        return $this;
    }

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'status' => $this->status,
            'message' => $this->message,
            'result' => $this->result
        ], $this->statusCode);
    }
}
