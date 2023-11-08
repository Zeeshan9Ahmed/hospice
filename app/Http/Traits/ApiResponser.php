<?php

namespace App\Http\Traits;

use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Api Responser Trait
|--------------------------------------------------------------------------
|
| This trait will be used for any response we sent to clients.
|
*/

trait ApiResponser
{
	/**
     * Return a success JSON response.
     *
     * @param  array|string  $data
     * @param  string  $message
     * @param  int|null  $code
     * @return \Illuminate\Http\JsonResponse
     */
	protected function success(string $message = null, $data = null, int $code = 200)
	{
		if(!empty($data)){
			return response()->json([
				'status' => 1,
				'message' => $message,
				'data' => $data
			], $code);
		}
		else{
			return response()->json([
				'status' => 1,
				'message' => $message
			], $code);
		}
	}

	/**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array|string|null  $data
     * @return \Illuminate\Http\JsonResponse
     */
	protected function error(string $message = null, int $code, $data = null)
	{
		return response()->json([
			'status' => 0,
			'message' => $message
		], $code);
	}

}
