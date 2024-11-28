<?php

namespace BlockeraAI\SiteToolkit\Http\Controller;

use BlockeraAI\SiteToolkit\Setup;
use Psr\Http\Message\ResponseInterface;
use BlockeraAI\SiteToolkit\Entities\UserEntity;
use GuzzleHttp\Psr7\ServerRequest;

class AuthController
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
        return is_user_logged_in() && $request->get_header('X-WP-Nonce') === wp_create_nonce('wp_rest');
    }

    /**
     * Authorize the request.
     *
     * @param \WP_REST_Request $request The request object.
     *
     * @return ResponseInterface The response object.
     */
    public function authorize(\WP_REST_Request $request = null): ResponseInterface
    {
        // Convert WP request to PSR-7 request.
        $psr7Request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals()
            ->withQueryParams($request->get_query_params());

        // Process the request
        $response = new \GuzzleHttp\Psr7\Response();

        // Assuming $server is your OAuth server instance.
        $authRequest = $this->setup->getServer()->validateAuthorizationRequest($psr7Request);

        // Get the current user.
        $user = wp_get_current_user();

        // Set user entity.
        $authRequest->setUser(new UserEntity($user->ID));

        // Approve the request.
        $authRequest->setAuthorizationApproved(true);

        // Complete the authorization.
        $completeAuth = $this->setup->getServer()->completeAuthorizationRequest($authRequest, $response);

        do_action('blockera-site-toolkit/rest/post/authorize', $completeAuth, $authRequest->getGrantTypeId(), $authRequest->getClient()->getIdentifier());

        return $completeAuth;
    }
}
