<?php

namespace App\Http\Traits;


use App\Http\Controllers\ApiController;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class ApiExceptions
{
    private $apiResponse;

    public function __construct(ApiController $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    public function getExceptionResponse($request, $e)
    {
        $message = array();
        if($this->isModel($e))
        {
            $message['reason'] = 'Data not found.';
            return $this->apiResponse->respondNotFound($message);
        }
        elseif($this->isHttp($e))
        {
            $message['reason'] = 'Incorrect request.';
            return $this->apiResponse->respondNotFound($message);
        }
        elseif($this->isAuth($e))
        {
            $message['reason'] = 'Unauthenticated or Token expired.';
            return $this->apiResponse->respondForbidden($message);
        }
        elseif($this->isTimeout($e)){
            $message['reason'] = 'Request Timeout. Api is down.';
            return $this->apiResponse->requestTimeout($message);
        }
        elseif($this->isRoute($e))
        {
            $message['reason'] = 'Request does not contain json header or token is incorrect.';
            return $this->apiResponse->respondForbidden($message);
        }
        elseif($this->isThrottle($e)) {
            $message['reason'] = $e->getMessage();
            $message['retry-after'] = $e->getHeaders()['Retry-After'].' seconds.';
            return $this->apiResponse->respondTooManyAttempts($message);
        }
        else
        {
            $message['reason'] = $e->getMessage();
            return $this->apiResponse->respondNotFound($message);
        }
    }

    private function isModel($e)
    {
        return $e instanceof ModelNotFoundException;
    }

    private function isHttp($e)
    {
        return $e instanceof NotFoundHttpException;
    }

    private function isAuth($e)
    {
        return $e instanceof AuthenticationException;
    }
    private function isTimeout($e)
    {
        return $e instanceof ConnectionException;
    }
    private function isRoute($e)
    {
        return $e instanceof RouteNotFoundException;
    }
    private function isThrottle($e)
    {
        return $e instanceof ThrottleRequestsException;
    }
}
