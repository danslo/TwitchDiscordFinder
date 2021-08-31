<?php

declare(strict_types=1);

namespace TwitchDiscordFinder;

use DI\FactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TwitchDiscordFinder\Discord\Recon;

class ReconCommand extends Command
{
    private const OPTION_DISCORD_TOKEN = 'discord-token';
    private const OPTION_GUILD_ID = 'guild-id';

    public function __construct(private FactoryInterface $factory, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->addOption(self::OPTION_DISCORD_TOKEN, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::OPTION_GUILD_ID, null, InputOption::VALUE_REQUIRED)
            ->setName('tdf:recon');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Recon $recon */
        $recon = $this->factory->make(Recon::class, [
            'output' => $output,
            'token' => $input->getOption(self::OPTION_DISCORD_TOKEN)
        ]);

        $result = $recon->find($input->getOption(self::OPTION_GUILD_ID));

        printf("Open VC: %s\n", implode(', ', $result->getChannelsWithOpenVC()));
        printf("TTS: %s\n", implode(', ', $result->getChannelsWithTTS()));

        return 0;
    }
}
