<?php

declare(strict_types=1);

namespace TwitchDiscordFinder;

use TwitchDiscordFinder\Twitch\StreamFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindCommand extends Command
{
    private const ARGUMENT_GAME = 'game';

    public function __construct(private StreamFinder $streamFinder, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->addArgument(self::ARGUMENT_GAME, InputArgument::REQUIRED)
            ->setName('tdf:find');
    }
    
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        echo implode(PHP_EOL, array_map(function($stream) {
            return $stream->getStreamer();
        }, $this->streamFinder->find([], $input->getArgument(self::ARGUMENT_GAME))));

        return 0;
    }
}
