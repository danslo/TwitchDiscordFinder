<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Twitch;

use GraphQL\Auth\AuthInterface;
use Psr\Http\Client\ClientInterface;

class Client extends \GraphQL\Client
{
    private const ENDPOINT  = 'https://gql.twitch.tv/gql';
    private const PROXY     = 'socks5://localhost:9050';
    private const CLIENT_ID = 'kimne78kx3ncx6brgo4mv6wki5h1ko';

    public function __construct(
        string $endpointUrl = self::ENDPOINT,
        array $authorizationHeaders = ['Client-Id' => self::CLIENT_ID],
        array $httpOptions = [/*'proxy' => ['https' => self::PROXY]*/],
        ClientInterface $httpClient = null,
        string $requestMethod = 'POST',
        AuthInterface $auth = null
    ) {
        parent::__construct($endpointUrl, $authorizationHeaders, $httpOptions, $httpClient, $requestMethod, $auth);
    }
}
