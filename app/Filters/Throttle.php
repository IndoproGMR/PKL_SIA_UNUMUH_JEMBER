<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Throttle implements FilterInterface
{
    /**
     * This is a demo implementation of using the Throttler class
     * to implement rate limiting for your application.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = Services::throttler();

        $error = [
            'status'  => 'error',
            'message' => 'Too Many Request',
        ];

        // Restrict an IP address to no more than 1 request
        // per second across the entire site.
        // if ($throttler->check("test", 1, MINUTE) === false) {
        if ($throttler->check(md5($request->getIPAddress()), 60, MINUTE) === false) {
            return Services::response()->setJSON($error);
        }
    }

    /**
     * We don't have anything to do here.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ...
    }
}
