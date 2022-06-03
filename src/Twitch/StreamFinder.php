<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Twitch;

class StreamFinder
{
    public function __construct(private Client $twitchClient, private Queries $queries) {}

    public function find(array $tags, string $game = null, int $minViewers = 5): array
    {
        $cursor = null;
        $streams = [];

        do {
            if ($game !== null) {
                $edges = $this->twitchClient->runQuery(
                    $this->queries->getGamesQuery($game, $tags, $cursor))->getData()->game->streams->edges ?? [];
            } else {
                $edges = $this->twitchClient->runQuery(
                    $this->queries->getStreamsQuery($tags, $cursor))->getData()->streams->edges ?? [];
            }

            foreach ($edges as $edge) {
                $cursor = $edge->cursor;
                if ($edge->node->viewersCount === 0 || $edge->node->viewersCount < $minViewers) {
                    return $streams;
                }
                $streams[] = new Stream(
                    $edge->node->title,
                    $edge->node->broadcaster->displayName,
                    'https://twitch.tv/' . $edge->node->broadcaster->displayName,
                    $edge->node->previewImageURL,
                    $edge->node->viewersCount,
                    $edge->node->game->displayName ?? ''
                );
            }
        } while (true);

        return $streams;
    }
}
