<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];
    
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
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
        //
    }
    
    /**
     * @inheritDoc
     */
    public function report(Throwable $e)
    {
        // Kill reporting if this is an "access denied" (code 9) OAuthServerException.
        if (
            ($e instanceof \League\OAuth2\Server\Exception\OAuthServerException &&
                ($e->getCode() == 9 || $e->getCode() == 8 || $e->getCode() == 10)) ||
            preg_match('/The refresh token is invalid/', $e->getMessage())
        ) {
            return;
        }
        
        parent::report($e);
    }
}
