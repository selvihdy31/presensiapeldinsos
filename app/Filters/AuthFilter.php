<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $logged_in = $session->get('logged_in');
        $role = $session->get('role');

        // Log untuk debug
        log_message('debug', '=== AuthFilter Debug ===');
        log_message('debug', 'URI: ' . $request->getUri());
        log_message('debug', 'Method: ' . $request->getMethod());
        log_message('debug', 'Content-Type: ' . $request->getHeaderLine('Content-Type'));
        log_message('debug', 'X-Requested-With: ' . $request->getHeaderLine('X-Requested-With'));
        log_message('debug', 'Logged In: ' . ($logged_in ? 'Yes' : 'No'));
        log_message('debug', 'Role: ' . $role);
        log_message('debug', 'Arguments: ' . json_encode($arguments));

        // Jika belum login
        if (!$logged_in) {
            log_message('debug', 'User not logged in');

            // Cek apakah AJAX request (multiple methods)
            $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' 
                   || strpos($request->getHeaderLine('Accept'), 'application/json') !== false;

            if ($isAjax) {
                log_message('debug', 'AJAX request - Returning JSON 401');
                return response()
                    ->setStatusCode(401)
                    ->setContentType('application/json')
                    ->setJSON([
                        'success' => false,
                        'message' => 'Silakan login terlebih dahulu'
                    ]);
            }

            log_message('debug', 'Regular request - Redirecting to login');
            return redirect()->to(base_url('login'))->with('error', 'Silakan login terlebih dahulu');
        }

        log_message('debug', 'User authorized - Proceeding');
        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}