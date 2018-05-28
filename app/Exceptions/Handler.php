<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Monolog\Handler\MailHandler;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $exception
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof HttpException) {
            $status = 0;
            $msg = $exception->getMessage();
            $data['headers']=$exception->getHeaders();
            $data['statusCode']=$exception->getStatusCode();
            $return = collect(compact('status',  'msg','data'));
            switch ($data['statusCode']) {
                case Response::HTTP_TOO_MANY_REQUESTS:
                    break;
                case Response::HTTP_UNAUTHORIZED:
                    return parent::render($request, $exception);
                    break;
                default;
                    break;

            }
            return JsonResponse::create($return);
        }
        if ($exception instanceof ValidationException) {
            return JsonResponse::create(['status' => 0, 'msg' => '参数错误', 'errors' => $exception->errors()]);
        }

        if(config('app.debug')){
            $log = new Logger('telegram_channel');

            $handler = new TelegramHandler();
            $log->pushHandler($handler);

            $log->debug($exception->getMessage());
        }
//        $request->headers->set('Accept','Application/json');
        return parent::render($request, $exception);
    }
}
