<?php

namespace App\Exceptions;

use App\Traits\ResponseTrait; // Assuming your ResponseTrait is in App\Traits
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ResponseTrait; // Use your ResponseTrait here

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) { // Check if it's an API request
            if ($exception instanceof ValidationException) {
                return $this->apiResponse('Validation failed', $exception->errors(), 422);
            }

            if ($exception instanceof ApiException) { // Handle your custom API exceptions
                return $this->apiResponse($exception->getMessage(), $exception->getData(), $exception->getCode());
            }

            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->apiResponse('Resource not found', null, 404);
            }

            // Catch any other general exceptions
            return $this->apiResponse($exception->getMessage(), null, $exception->getCode() ?: 500);
        }

        return parent::render($request, $exception);
    }
}
