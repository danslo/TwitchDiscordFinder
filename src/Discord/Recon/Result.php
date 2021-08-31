<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Discord\Recon;

class Result
{
    public function __construct(private array $channelsWithOpenVC = [], private array $channelsWithTTS = []) {}

    public function getChannelsWithOpenVC(): array
    {
        return $this->channelsWithOpenVC;
    }

    public function getChannelsWithTTS(): array
    {
        return $this->channelsWithTTS;
    }

    public function addChannelWithTTS(string $channelWithTTS): Result
    {
        $this->channelsWithTTS[$channelWithTTS] = $channelWithTTS;
        return $this;
    }

    public function addChannelWithOpenVC(string $channelWithOpenVC): Result
    {
        $this->channelsWithOpenVC[$channelWithOpenVC] = $channelWithOpenVC;
        return $this;
    }

    public function clear(): void
    {
        $this->channelsWithOpenVC = [];
        $this->channelsWithTTS = [];
    }
}
