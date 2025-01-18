<?php

namespace BlockeraAI\SiteToolkit\Services;

class FileUploadService
{
	/**
	 * User credentials.
	 *
	 * @var array
	 */
	private array $userCredentials;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->userCredentials = bsaGetUserAccessToken();
	}

	/**
	 * Upload file.
	 *
	 * @param array $params The params.
	 *
	 * @return void
	 */
	public function upload(array $params): void
	{
		wp_remote_post(
			bsaGetConfig('BSA_API_BASE_URL') . '/secure-downloads/v1/files',
		);
	}
}
