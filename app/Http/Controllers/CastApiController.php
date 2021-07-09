<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CastApiController extends ApiController
{
    public function getCastStatus()
    {
        return Http::timeout(3)
                    ->get('https://webinjector.thebuytoletbusiness.com/apis/v2/status/');
    }

    public function generateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referrer' => 'bail|required',
            'referrerapikey' => 'required'
        ]);

        $message = array();
        if($validator->fails())
        {
            foreach ($validator->errors()->toArray() as $key => $error) {
                $message[$key] = $error[0];
            }
            return $this->respondValidation($message);
        }

        $castStatusResponse = $this->getCastStatus();

        if($castStatusResponse->body() == 'OK')
        {
            $castAuthResponse = Http::get('https://webinjector.thebuytoletbusiness.com/apis/v2/querylogin', [
                'referrer' => $request->input('referrer'),
                'referrerapikey' => $request->input('referrerapikey'),
            ]);

            $arrCastAuthResponse = $castAuthResponse->collect()->toArray();

            $isAuthenticated = $arrCastAuthResponse['cast_response']['authenticated-result']['authenticated'] ?? false;

            $isApiLocked = $arrCastAuthResponse['cast_response']['authentication-detail']['apilocked'] ?? false;

            $apiLockReason = $arrCastAuthResponse['cast_response']['authentication-detail']['apilockedreason'] ?? false;

            if($isAuthenticated && $isApiLocked)
            {
                $user = User::where('referrer', $request->input('referrer'))
                        ->where('referrerApiToken', $request->input('referrerapikey'))
                        ->first();

                if(!$user)
                {
                    $user = User::create([
                        'name' => $request->input('referrer'),
                        'email' => 'pranay.baddam@dynamo.co.uk',
                        'referrer' => $request->input('referrer'),
                        'referrerApiToken' => $request->input('referrerapikey')
                    ]);
                }

                if($user->tokens->count() == 0)
                {
                    $token = $user->createToken('AreApiToken')->plainTextToken;
                }
                else
                {
                    $user->tokens()->delete();
                    $token = $user->createToken('AresApiToken')->plainTextToken;
                }

                $data = [
                    'data' => [
                        'access_token' => $token,
                        'message' => 'Token is valid for '.config("sanctum.expiration").' minutes',
                    ]
                ];

                return $this->respond($data);
            }
            if(!$isAuthenticated)
            {
                $message['isAuthenticated'] = $isAuthenticated;
                $message['reason'] = 'Credentials do not match with our records !';
                return $this->respondNotFound($message);
            }
            if($isApiLocked)
            {
                $message['isApiLocked'] = $isApiLocked;
                $message['reason'] = $apiLockReason;
                return $this->respondNotFound($message);
            }
        }

        $message['reason'] = 'Cast api is not working at the moment !';
        return $this->respondInternalError($message);
    }

    public function getReferrer($id)
    {
        $referrer = User::findOrFail($id);

        $data = [
            'data' => [
                'referrer' => $referrer->toArray(),
                'message' => 'success'
            ]
        ];

        return $this->respond($data);
    }
}
