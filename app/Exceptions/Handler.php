<?php

namespace Pterodactyl\Exceptions;

use Log;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        return parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception                $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($request->expectsJson() || $request->isJson() || $request->is(...config('pterodactyl.json_routes'))) {
            $exception = $this->prepareException($exception);

            if (config('app.debug')) {
                $report = [
                    'code' => (! $this->isHttpException($exception)) ?: $exception->getStatusCode(),
                    'message' => class_basename($exception) . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine(),
                ];
            }

            $response = response()->json([
                'error' => (config('app.debug')) ? $exception->getMessage() : 'An unhandled exception was encountered with this request.',
                'exception' => ! isset($report) ?: $report,
            ], ($this->isHttpException($exception)) ? $exception->getStatusCode() : 500, [], JSON_UNESCAPED_SLASHES);

            parent::report($exception);
        }

        return (isset($response)) ? $response : parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request                  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('auth.login'));
    }
}
