<?php
declare(strict_types=1);

class MY_Loader extends CI_Loader
{
	/**
	 * Returns a JSON response data to the output
	 * @param mixed $response
	 * @return void
	 */
	public function jsonResponse($response) {
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode($response);
	}
}
