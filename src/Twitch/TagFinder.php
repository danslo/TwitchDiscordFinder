<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Twitch;

class TagFinder
{
    public function __construct(private Client $twitchClient, private Queries $queries) {}

    public function find(array $tagQueries): array
    {
        $tags = [];
        foreach ($this->twitchClient->runQuery($this->queries->getTagsQuery($tagQueries))->getData() as $tagData) {
            if (count($tagData) === 0) {
                continue;
            }
            $tags[] = (new Tag($tagData[0]->id, $tagData[0]->localizedDescription));
        }
        return $tags;
    }
}
