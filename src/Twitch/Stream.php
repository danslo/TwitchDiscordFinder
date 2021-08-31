<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Twitch;

class Stream
{
    public function __construct(
        private string $title,
        private string $streamer,
        private string $streamerURL,
        private string $previewURL,
        private int $viewers,
        private string $game,
    ) {}

    public function getGame(): string
    {
        return $this->game;
    }

    public function getViewers(): int
    {
        return $this->viewers;
    }

    public function getStreamer(): string
    {
        return $this->streamer;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStreamerURL(): string
    {
        return $this->streamerURL;
    }

    public function getPreviewURL(): string
    {
        return $this->previewURL;
    }
}
