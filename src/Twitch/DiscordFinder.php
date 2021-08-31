<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Twitch;

class DiscordFinder
{
    public function __construct(private Client $twitchClient, private Queries $queries) {}

    private function isDiscordUrl(string $url): bool
    {
        return str_starts_with($url, 'https://discord.gg/');
    }

    public function find(string $login): array
    {
        $data = $this->twitchClient->runQuery($this->queries->getUserAboutQuery($login))->getData();

        $urls = [];
        foreach ($data->user->channel->socialMedias ?? [] as $socialMedia) {
            $urls[] = $socialMedia->url;
        }

        foreach ($data->user->panels ?? [] as $panel) {
            if (isset($panel->linkURL)) {
                $urls[] = $panel->linkURL;
            }
        }

        return array_unique(array_filter($urls, [$this, 'isDiscordUrl']));
    }
}
