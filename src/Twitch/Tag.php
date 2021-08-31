<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Twitch;

class Tag
{
    public function __construct(private string $id, private string $description) {}

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
