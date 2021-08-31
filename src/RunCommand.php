<?php

declare(strict_types=1);

namespace TwitchDiscordFinder;

use DI\FactoryInterface;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use React\Promise\Deferred;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TwitchDiscordFinder\Discord\Recon;
use TwitchDiscordFinder\Twitch\DiscordFinder;
use TwitchDiscordFinder\Twitch\Stream;
use TwitchDiscordFinder\Twitch\StreamFinder;
use TwitchDiscordFinder\Twitch\Tag;
use TwitchDiscordFinder\Twitch\TagFinder;

class RunCommand extends Command
{
    private const ARGUMENT_TAGS = 'tags';
    private const OPTION_DISCORD_TOKEN = 'discord-token';
    private const OPTION_RECON_TOKEN = 'recon-token';

    private ?Recon $recon;

    public function __construct(
        private FactoryInterface $factory,
        private TagFinder $tagFinder,
        private StreamFinder $streamFinder,
        private DiscordFinder $discordFinder,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->addArgument(self::ARGUMENT_TAGS, InputArgument::IS_ARRAY)
            ->addOption(self::OPTION_DISCORD_TOKEN, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::OPTION_RECON_TOKEN, null, InputOption::VALUE_OPTIONAL)
            ->setName('tdf:run');
    }

    private function sendMessage(Channel $channel, string $message): void
    {
        $channel->sendMessage(MessageBuilder::new()->setContent($message));
    }

    private function getEmbedFromStream(Discord $discord, Stream $stream): ?Embed
    {
        $embed = new Embed($discord);
        $embed->setTitle($stream->getStreamer())
            ->setDescription($stream->getTitle())
            ->setURL($stream->getStreamerURL())
            ->setThumbnail($stream->getPreviewURL())
            ->addFieldValues('Game', $stream->getGame())
            ->addFieldValues('Viewers', (string) $stream->getViewers());

        $discordUrls = $this->discordFinder->find($stream->getStreamer());
        if (count($discordUrls) === 0) {
            return null;
        }
        $embed->addFieldValues('Discord', implode(PHP_EOL, $discordUrls));

        return $embed;
    }

    private function getDiscordsFromEmbed(Embed $embed): array
    {
        return explode(PHP_EOL, $embed->fields->filter(function(Field $field) {
            return $field->name === 'Discord';
        })->first());
    }

    private function performRecon(Embed $embed): void
    {
        if ($this->recon === null) {
            return;
        }

        foreach ($this->getDiscordsFromEmbed($embed) as $discordUrl) {
            $exploded = explode('/', $discordUrl);
            $result = $this->recon->find(end($exploded));
            if ($result->getChannelsWithTTS()) {
                $embed->addFieldValues('TTS', substr(implode(', ', $result->getChannelsWithTTS()), 0, 1024));
            }
            if ($result->getChannelsWithOpenVC()) {
                $embed->addFieldValues('Open VC', substr(implode(', ', $result->getChannelsWithOpenVC()), 0, 1024));
            }
        }
    }

    private function findTags(Channel $channel, array $parts): array
    {
        $this->sendMessage($channel, 'Finding tags...');
        $tags = $this->tagFinder->find($parts);
        if (count($tags) === 0) {
            throw new \Exception('No tags found!');
        } else {
            $this->sendMessage($channel, implode(PHP_EOL, array_map(function (Tag $tag) {
                return $tag->getId() . ' -> ' . $tag->getDescription();
            }, $tags)));
        }
        return $tags;
    }

    private function findStreams(Channel $channel, array $tags, string $game = null): array
    {
        $this->sendMessage($channel, 'Finding streams...');
        $streams = $this->streamFinder->find($tags, $game);
        if (count($streams) === 0) {
            throw new \Exception('No streams found!');
        }
        return $streams;
    }

    private function createEmbeds(Discord $discord, array $streams): array
    {
        $embeds = [];
        /** @var Stream $stream */
        foreach ($streams as $stream) {
            $embed = $this->getEmbedFromStream($discord, $stream);
            if ($embed !== null) {
                $this->performRecon($embed);
            }
            $embeds[] = $embed;
        }
        return $embeds;
    }

    private function getMessageDeferred(Message $message, Discord $discord, string $game = null): Deferred
    {
        $deferred = $this->factory->make(Deferred::class);
        $deferred->promise()
            ->then(function (array $parts) use ($message) {
                return $this->findTags($message->channel, $parts);
            })
            ->then(function (array $tags) use ($message, $game) {
                return $this->findStreams($message->channel, $tags, $game);
            })
            ->then(function (array $streams) use ($message, $discord) {
                return $this->createEmbeds($discord, $streams);
            })
            ->then(function (array $embeds) use ($message) {
                foreach ($embeds as $embed) {
                    $message->channel->sendEmbed($embed);
                }
            })
            ->otherwise(function (\Exception $e) use ($message) {
                $this->sendMessage($message->channel, $e->getMessage());
            });
        return $deferred;
    }

    private function handleDiscordMessage(Message $message, Discord $discord): void
    {
        $startsWithGame = str_starts_with($message->content, '!game');
        if (!str_starts_with($message->content, '!discord') && !$startsWithGame) {
            return;
        }

        $parts = $this->getCommandParts($message->content);
        if (count($parts) === 0) {
            $this->sendMessage($message->channel, 'Please provide arguments.');
            return;
        }

        $this->getMessageDeferred($message, $discord, $startsWithGame ? array_shift($parts) : null)
            ->resolve($parts);
    }

    private function getCommandParts(string $content): array
    {
        $parts = str_getcsv($content, ' ');
        array_shift($parts);
        return $parts;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new Logger('Logger');
        $logger->pushHandler(new NullHandler());

        /** @var Discord $discord */
        $discord = $this->factory->make(
            Discord::class,
            [
                'options' => [
                    'token'  => $input->getOption(self::OPTION_DISCORD_TOKEN),
                    'logger' => $logger
                ],
            ]
        );

        $reconToken = $input->getOption(self::OPTION_RECON_TOKEN);
        if ($reconToken) {
            $this->recon = $this->factory->make(Recon::class, [
                'output' => $output,
                'token' => $reconToken
            ]);
        }

        $discord->on('ready', function (Discord $discord) use ($output) {
            $output->writeln('TDF Started.');
            $discord->on('message', function (Message $message, Discord $discord) {
                $this->handleDiscordMessage($message, $discord);
            });
        });

        $discord->run();

        return 0;
    }
}
