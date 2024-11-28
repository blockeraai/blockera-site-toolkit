<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use BlockeraAI\SiteToolkit\Setup;

class AccessTokenController
{
    /**
     * The Setup instance.
     *
     * @var Setup
     */
    protected Setup $setup;

    /**
     * Constructor.
     *
     * @param Setup $setup The setup instance.
     */
    public function __construct(Setup $setup)
    {
        $this->setup = $setup;
    }

    /**
     * Check if the user is logged in.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return boolean true on success, false on otherwise!
     */
    public function permission(\WP_REST_Request $request): bool
    {
        return true;
    }

    public function accessToken(\WP_REST_Request $request)
    {
        // Process the request
        $response = new \GuzzleHttp\Psr7\Response();

        try {
            // Convert WP request to PSR-7 request
            $psr7Request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

            $response = $this->setup->getServer()->respondToAccessTokenRequest($psr7Request, $response);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {

            return new \WP_REST_Response(['error' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()], 400);
        }
    }
}
