<?php
declare(strict_types=1);

namespace Controllers\admin;

use System\Request;
use System\Response;
use System\Session;
use Exception;

/**
 * AdminServicesController
 *
 * Handles AJAX form submissions, service requests, and API-style
 * admin operations for the admin panel.
 */
class AdminServicesController extends AdminController
{ 
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->initSession();
        $this->verifyAdminSession();
    }

    /**
     * Initialize session.
     */
    protected function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $this->session = new Session();
    }

    /**
     * Ensure admin is logged in.
     */
    protected function verifyAdminSession(): void
    {
        $isLoggedIn = (bool) $this->session->get('admin_logged_in', false);
        if (!$isLoggedIn) {
            $this->deny('Admin session expired or not authenticated', 401);
        }
    }

    /**
     * POST /api/upload_chunk
     */
    public function upload_chunk(): void
    {
        try {
            // Gather inputs: prefer framework request helpers, fallback to $_POST
            $fileId   = (string) ($this->request->input('fileId') ?? $_POST['fileId'] ?? '');
            $index    = isset($_POST['index']) ? intval($_POST['index']) : intval($this->request->input('index') ?? 0);
            $total    = isset($_POST['total']) ? intval($_POST['total']) : intval($this->request->input('total') ?? 0);
            $filename = (string) ($this->request->input('filename') ?? $_POST['filename'] ?? ($_FILES['chunk']['name'] ?? 'unknown'));
            $mimeType = (string) ($this->request->input('mimeType') ?? $_POST['mimeType'] ?? ($_FILES['chunk']['type'] ?? 'application/octet-stream'));

            // Optionally allow overriding storage paths via config
            $tempBase = defined('BASEPATH') ? BASEPATH . '/storage/uploads/tmp_chunks' : __DIR__ . '/../../storage/uploads/tmp_chunks';

            $options = [
                'tempBase' => $tempBase,
                'fileId'   => $fileId,
                'index'    => $index,
                'total'    => $total,
                'filename' => $filename,
                'mimeType' => $mimeType
                // NOTE: chunk file is read from $_FILES['chunk'] inside handleUploadChunk
            ];

            $result = $this->handleUploadChunk($options);

            if (!empty($result['status']) && $result['status'] === true) {
                // standard success envelope
                $this->success([
                    'fileId' => $result['fileId'] ?? $fileId,
                    'index'  => $result['index'] ?? $index,
                ], $result['message'] ?? 'Chunk stored', 200);
                return;
            }

            // validation / store error
            $this->jsonErrorResponse($result['message'] ?? 'Failed to store chunk', 400);
            return;

        } catch (Exception $e) {
            $this->handleException($e, 500);
            return;
        }
    }

    /**
     * POST /api/upload_complete
     */
    public function upload_complete(): void
    {
        try {
            // Accept either JSON body or form params
            $raw = (string) file_get_contents('php://input');
            $body = @json_decode($raw, true);
            if (!is_array($body)) $body = $_POST;

            $fileId   = isset($body['fileId']) ? (string)$body['fileId'] : '';
            $filename = $body['filename'] ?? null;
            $total    = isset($body['total']) ? intval($body['total']) : null;

            if ($fileId === '') {
                $this->jsonErrorResponse('Missing fileId', 400);
                return;
            }

            $tempBase    = defined('BASEPATH') ? BASEPATH . '/storage/uploads/tmp_chunks' : __DIR__ . '/../../storage/uploads/tmp_chunks';
            $uploadsBase = defined('BASEPATH') ? BASEPATH . '/storage/uploads/final' : __DIR__ . '/../../storage/uploads/final';
            $maxSize     = (50 * 1024 * 1024);

            $options = [
                'tempBase'    => $tempBase,
                'uploadsBase' => $uploadsBase,
                'maxSize'     => $maxSize,
                'fileId'      => $fileId,
                'filename'    => $filename,
                'total'       => $total
            ];

            $result = $this->handleUploadComplete($options);

            if (!empty($result['status']) && $result['status'] === true) {
                // Return assembled file info
                $this->success([
                    'fileId'   => $result['fileId'],
                    'filename' => basename($result['path']),
                    'path'     => $result['path'],
                    'size'     => $result['size'],
                ], $result['message'] ?? 'File assembled', 200);
                return;
            }

            $this->jsonErrorResponse($result['message'] ?? 'Failed to assemble file', 400);
            return;

        } catch (Exception $e) {
            $this->handleException($e, 500);
            return;
        }
    }

    /**
     * Example: Admin logout request via AJAX.
     * POST /admin/services/logout
     */
    public function logout(): void
    {
        try {
            $this->session->remove('admin_logged_in');
            $this->session->remove('admin');
            $this->success([], 'Logged out successfully', 200);
        } catch (Exception $e) {
            $this->handleException($e, 500);
        }
    }
}
