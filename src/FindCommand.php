<?php

declare(strict_types=1);

namespace TwitchDiscordFinder;

use TwitchDiscordFinder\Twitch\StreamFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindCommand extends Command
{
    private const ARGUMENT_GAME = 'game';
    private const OPTION_MIN_VIEWERS = 'min-viewers';

    public function __construct(private StreamFinder $streamFinder, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->addArgument(self::ARGUMENT_GAME, InputArgument::REQUIRED)
            ->addOption(self::OPTION_MIN_VIEWERS, null, InputOption::VALUE_OPTIONAL, 'Minimum number of viewers', 10)
            ->setName('tdf:find');
    }
    
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        echo implode(
            PHP_EOL,
            array_map(function($stream) {
                return $stream->getStreamer() . '#' . $stream->getViewers();
            }, $this->streamFinder->find(
                [],
                $input->getArgument(self::ARGUMENT_GAME),
                (int) $input->getOption(self::OPTION_MIN_VIEWERS)
            ))
        );
        return 0;
    }
}
