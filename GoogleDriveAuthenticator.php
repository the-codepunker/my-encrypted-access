<?php
namespace Codepunker\GoogleDrive;

/**
 * Handles CLI authentication with gDrive
 * regenerates tokens and such...
 */
class GoogleDriveAuthenticator
{
    const APPLICATION_NAME = 'My Access Management';
    const CREDENTIALS_PATH = __DIR__ . '/credentials.json';
    const CLIENT_SECRET_PATH = __DIR__ . '/secret.json';
    const SCOPES = 'https://www.googleapis.com/auth/drive';

    private $gclient;

    /**
     * create a google api client instance and store it as a class property
     */
    public function __construct()
    {
        $this->gclient = new \Google_Client();
        $this->gclient->setApplicationName(self::APPLICATION_NAME);
        $this->gclient->addScope(self::SCOPES);
        $this->gclient->setAuthConfig(self::CLIENT_SECRET_PATH);
        $this->gclient->setAccessType('offline');
        $this->setClient();
    }

    /**
     * if no access token create one
     * if expired refresh...
     * @return void
     */
    private function setClient()
    {
        if (!file_exists(self::CREDENTIALS_PATH)) {
            $authUrl = $this->gclient->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            $tkn = $this->gclient->authenticate($authCode);
            file_put_contents(self::CREDENTIALS_PATH, json_encode($tkn));
            printf("Credentials saved to %s\n", self::CREDENTIALS_PATH);
        }

        $tkn = file_get_contents(self::CREDENTIALS_PATH);
        $this->gclient->setAccessToken($tkn);
        if ($this->gclient->isAccessTokenExpired()) {
            $this->gclient->refreshToken($this->gclient->getRefreshToken());
            file_put_contents(self::CREDENTIALS_PATH, json_encode($this->gclient->getAccessToken()));
        }
    }

    /**
     * returns the authenticated Google Api Client
     * @return Google_Client
     */
    public function getClient() :\Google_Client
    {
        return $this->gclient;
    }
}
