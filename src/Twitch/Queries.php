<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Twitch;

use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\RawObject;

class Queries
{
    public const PAGE_SIZE = 30;

    public function getUserAboutQuery(string $login): Query
    {
        return (new Query('user'))
            ->setSelectionSet([
                (new Query('channel'))
                    ->setSelectionSet([
                        (new Query('socialMedias'))
                            ->setSelectionSet(['url'])
                    ]),
                (new Query('panels'))
                    ->setSelectionSet([
                        (new InlineFragment('DefaultPanel'))
                            ->setSelectionSet(['linkURL'])
                    ])
            ])
            ->setArguments(['login' => $login]);
    }

    public function getStreamsQuery(array $tags, string $cursor = null): Query
    {
        return (new Query('streams'))
            ->setSelectionSet([
                (new Query('edges'))
                    ->setSelectionSet([
                        'cursor',
                        (new Query('node'))
                            ->setSelectionSet([
                                'title',
                                'viewersCount',
                                'previewImageURL(width:960, height:540)',
                                (new Query('broadcaster'))
                                    ->setSelectionSet(['displayName']),
                                (new Query('game'))
                                    ->setSelectionSet(['displayName'])
                            ])
                    ])
            ])
            ->setArguments([
                'first' => self::PAGE_SIZE,
                'after' => $cursor,
                'options' => new RawObject(sprintf('{tags:[%s]}', implode(',', array_map(function(Tag $tag) {
                    return '"' . $tag->getId() . '"';
                }, $tags))))
            ]);
    }

    public function getGamesQuery(string $game, array $tags, string $cursor = null): Query
    {
        return (new Query('game'))
            ->setArguments(['name' => $game])
            ->setSelectionSet([
                'streams' => $this->getStreamsQuery($tags, $cursor)
            ]);
    }

    public function getTagsQuery(array $tags): Query
    {
        $queries = [];
        foreach ($tags as $tag) {
            $queries[] = (new Query('searchLiveTags', preg_replace('/[^a-zA-Z]+/', '', $tag)))
                ->setSelectionSet(['id', 'localizedDescription'])
                ->setArguments(['userQuery' => $tag, 'limit' => 100]);
        }
        return (new Query())->setSelectionSet($queries);
    }
}
