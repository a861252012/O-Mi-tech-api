<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    protected $logger;


    public function __construct(Container $container)
    {

        $this->logger = app()->make(LoggerInterface::class);
        $this->logger->pushProcessor($this->formatCurl());
        parent::__construct($container);
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     *
     * @throws /Exception
     */
    public function report(Exception $exception)
    {
        if(parent::shouldntReport($exception)) {
            return;
        }

        try {
            $format = "#%datetime% > %level_name% > %message% \n%context% \n%extra%\n%request%\n\n\n";

            $handler = $this->logger->getHandlers()[0];
            $handler->setFormatter($this->getFormat($format, 1));
            $this->logger->pushHandler($handler);
            $this->logger->error(
                $exception->getMessage(),
                array_merge($this->context(), ['exception' => $exception]
                ));
        } catch (Exception $ex) {
            parent::report($exception);
        }


    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $exception
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof HttpException) {
            $status = 0;
            $msg = $exception->getMessage();
            $data['headers'] = $exception->getHeaders();
            $data['statusCode'] = $exception->getStatusCode();
            $return = collect(compact('status', 'msg', 'data'));
            switch ($data['statusCode']) {
                case Response::HTTP_TOO_MANY_REQUESTS:
                    break;
                case Response::HTTP_UNAUTHORIZED:
                    return parent::render($request, $exception);
                default;
                    break;
            }
            return JsonResponse::create($return);
        }

        if ($exception instanceof ValidationException) {
            return JsonResponse::create(['status' => 0, 'msg' => '参数错误', 'errors' => $exception->errors()]);
        }

        if ((int)Redis::get('log') === 1) {
            try {
                $format = "%request%\n\n\n#%datetime% > %level_name% > %message% \n%context% \n\n%extra%";
                $log = $this->logger;
                $handler = new TelegramHandler();
                $handler->setFormatter($this->getFormat($format));
                $log->pushHandler($handler);
                $log->debug($exception->getMessage(), array_merge($this->context(), ['exception' => $exception]));
            } catch (Exception $e) {
                return parent::render($request, $exception);
            }

        }

        return parent::render($request, $exception);
    }

    /**
     * Get the default context variables for logging.
     *
     * @return array
     */
    protected function context(): array
    {
        try {
            return array_filter([
                'site' => \SiteSer::siteId() ?? '',
                'userId' => Auth::id(),
                'role' => Auth::user() ? Auth::user()->roled : null,
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function getFormat($format, $trace = false): LineFormatter
    {
        $obj = new LineFormatter($format);
        if ($trace) {
            $obj->includeStacktraces();
        }
        return $obj;
    }


    protected function formatCurl(): \Closure
    {
        return function ($recode) {
            if ('cli' === PHP_SAPI) {
                return $recode;
            }
            $header = Request::header();
            $url = Request::fullUrl();
            unset($header['cookie']);
            //-H 'Upgrade-Insecure-Requests: 1'
            $headerstr = ' ';
            array_walk($header, function ($v, $k) use (&$headerstr) {
                if (\in_array($k,['cf-connecting-ip','x-forwarded-for','cf-ipcountry','cf-ray'])) {
                    return;
                }
                $v[0] === '' || $headerstr .= " -H '$k: $v[0]'";
            });
            if ($uid = Auth::id()) {
                $headerstr .= " -H 'X-Test-V5: $uid'";
            }
            $tmp = "curl '" . $url . "'" . $headerstr;
            if ('POST' === Request::getMethod() && $data = Request::post()) {
                $tmp .= ' --data \'' . http_build_query($data) . "'";
            }
            $recode['request'] = $tmp . ' --compressed';
            return $recode;
        };

    }
}
