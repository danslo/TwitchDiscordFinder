<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Discord;

use GuzzleHttp\Client;

class Joiner
{
    private const DISCORD_API_INVITES_URL = 'https://discord.com/api/v9/invites';
    private const DISCORD_API_GUILDS_URL  = 'https://discord.com/api/v9/users/@me/guilds';
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36';
    private const PROXY = 'socks5://localhost:9050';

    public function __construct(
        private string $token,
        private Client $client,
        private bool $useProxy = true
    ) {}

    private function getOptions(): array
    {
        $options = [
            'headers' => [
                'authorization' => $this->token,
                'user-agent' => self::USER_AGENT,
                'accept' => '*/*',
            ]
        ];

        if ($this->useProxy) {
            $options['proxy'] = self::PROXY;
        }

        return $options;
    }

    public function join(string $server): array
    {
        $response = $this->client->post(sprintf('%s/%s', self::DISCORD_API_INVITES_URL, $server), $this->getOptions());
        return json_decode($response->getBody()->getContents(), true);
    }

    public function leave(int $guildId): bool
    {
        $response = $this->client->delete(sprintf('%s/%s', self::DISCORD_API_GUILDS_URL, $guildId), $this->getOptions());
        return $response->getStatusCode() === 204;
    }
}
