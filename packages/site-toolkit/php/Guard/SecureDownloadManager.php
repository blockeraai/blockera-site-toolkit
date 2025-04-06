<?php

namespace BlockeraAI\SiteToolkit\Guard;

use BlockeraAI\SiteToolkit\Setup;
use BlockeraAI\SiteToolkit\Repositories\SecureDownloadRepository;

class SecureDownloadManager
{
    /**
     * The token lifetime in seconds.
     *
     * @var int
     */
    private $tokenLifetime = 3600;

    /**
     * The maximum allowed downloads per link.
     *
     * @var int
     */
    private $maxDownloads = 3;

    /**
     * The setup instance.
     *
     * @var Setup
     */
    private Setup $app;

    /**
     * The secure download repository instance.
     *
     * @var SecureDownloadRepository
     */
    private SecureDownloadRepository $secureDownloadRepository;

    /**
     * Constructor.
     *
     * @param Setup $setup The setup instance.
     */
    public function __construct(Setup $setup, SecureDownloadRepository $secureDownloadRepository)
    {
        $this->app = $setup;
        $this->secureDownloadRepository = $secureDownloadRepository;
    }

    /**
     * Generate secure download link.
     *
     * @param string $fileId The file id.
     * @param string $clientId The client id.
     * @param string $subscriptionId The subscription id.
     *
     * @return string the signed url.
     */
    public function generateDownloadLink(string $fileId, string $clientId, string $subscriptionId): string
    {
        // Generate unique token.
        $token = $this->generateUniqueToken();

        // Current timestamp.
        $timestamp = time();

        // Store download link details in database.
        $downloadData = [
            'is_valid' => 1,
            'token' => $token,
            'download_count' => 0,
            'file_id' => $fileId,
            'client_id' => $clientId,
            'created_at' => date('Y-m-d H:i:s', $timestamp),
            'subscription_id' => $subscriptionId,
            'expires_at' => date('Y-m-d H:i:s', $timestamp + $this->tokenLifetime),
        ];

        $this->secureDownloadRepository->persistNewDownload($downloadData);

        // Generate signed URL.
        return add_query_arg([
            'action' => 'download',
            'token' => $token,
            'hash' => $this->generateSignature($token, $timestamp)
        ], site_url());
    }

    /**
     * Validate download link
     *
     * @param string $token The token.
     * @param string $hash The hash.
     *
     * @return bool true if the download link is valid, false otherwise.
     */
    public function validateDownloadLink(string $token, string $hash): bool
    {
        try {
            // Get download data from database.
            $downloadData = $this->secureDownloadRepository->getDownloadData($token);

            if (!$downloadData) {
                throw new \Exception('Invalid download link');
            }

            // Verify signature.
            if (!$this->verifySignature($token, strtotime($downloadData['created_at']), $hash)) {
                throw new \Exception('Invalid signature');
            }

            // Check if link has expired.
            if (time() > $downloadData['expires_at']) {
                throw new \Exception('Download link has expired');
            }

            // Check if subscription is still active.
            if (!$this->app->make('subscriptionRepository')->isActiveSubscription($downloadData['subscription_id'])) {
                throw new \Exception('Subscription is not active');
            }

            // Check download count.
            if ((int) $downloadData['download_count'] >= $this->maxDownloads) {
                throw new \Exception('Maximum download limit reached');
            }

            // Check if link is still valid.
            if (!(int)$downloadData['is_valid']) {
                throw new \Exception('Download link has been revoked');
            }

            // Verify client authentication and authorization.
            // if (!$this->verifyClientAccess()) {
            //     throw new \Exception('Unauthorized access');
            // }

            // Update download count.
            $this->secureDownloadRepository->incrementDownloadCount($token);

            return true;
        } catch (\Exception $e) {
            // Log the error.
            error_log('Download validation error: %s' . $e->getMessage());

            return false;
        }
    }

    /**
     * Generate unique token
     *
     * @return string the unique token.
     */
    private function generateUniqueToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate signature for URL
     *
     * @param string $token The token.
     * @param int $timestamp The timestamp.
     *
     * @return string the signature.
     */
    private function generateSignature(string $token, int $timestamp): string
    {
        $secretKey = get_option('download_secret_key');

        return hash_hmac('sha256', $token . $timestamp, $secretKey);
    }

    /**
     * Verify signature
     *
     * @param string $token The token.
     * @param int $timestamp The timestamp.
     * @param string $providedHash The provided hash.
     *
     * @return bool true if the signature is valid, false otherwise.
     */
    private function verifySignature(string $token, int $timestamp, string $providedHash): bool
    {
        $expectedHash = $this->generateSignature($token, $timestamp);

        return hash_equals($expectedHash, $providedHash);
    }

    /**
     * Verify client access.
     *
     * @return bool true if the client has access, false otherwise.
     */
    private function verifyClientAccess(): bool
    {
        try {
            $resourceServer = $this->app->getResourceServer();

            // Convert WP request to PSR-7 request.
            $psr7Request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

            // Validate the request.
            $resourceServer->validateAuthenticatedRequest($psr7Request);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Process download.
     *
     * @param string $token The token.
     * @param string $hash The hash.
     *
     * @return void
     */
    public function processDownload(string $token, string $hash): void
    {
        if ($this->validateDownloadLink($token, $hash)) {
            $downloadData = $this->secureDownloadRepository->getDownloadData($token);
            $file = $this->secureDownloadRepository->getFile($downloadData['file_id']);

            // Set appropriate headers
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
            header('Content-Length: ' . filesize($file['file']));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            // Output file.
            readfile($file['file']);
            exit;
        } else {
            wp_die(__('Invalid download link', 'blockera-site-toolkit'));
        }
    }
}
