<?php

declare(strict_types=1);

namespace TwitchDiscordFinder\Discord;

use DI\FactoryInterface;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Reaction;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use GuzzleHttp\Exception\RequestException;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use TwitchDiscordFinder\Discord\Recon\Result;

class Recon
{
    private Joiner $joiner;

    public function __construct(
        private string $token,
        private OutputInterface $output,
        private FactoryInterface $factory,
        private Result $result,
        private array $guildInfo = [],
        bool $useProxy = true
    ) {
        $this->joiner = $this->factory->make(Joiner::class, [
            'token' => $token,
            'useProxy' => $useProxy
        ]);
    }

    private function reactToRulesChannel(Channel $channel): void
    {
        $channel->getMessageHistory(['limit' => 5])->done(function (Collection $messages) {
            /** @var Message $message */
            foreach ($messages as $message) {
                /** @var Reaction $firstReaction */
                $firstReaction = $message->reactions->first();
                if ($firstReaction !== null && $firstReaction->me === false) {
                    $this->output->writeln(sprintf("Reacting to rules channel: %s.", $message->channel->name));
                    $message->react($firstReaction->emoji);
                }
            }
        });
    }

    private function reactToRulesChannels(Discord $discord, Guild $guild): void
    {
        $member = $this->getSelfGuildMember($discord, $guild);

        /** @var Channel $rulesChannel */
        $rulesChannels = $guild->channels->filter(function(Channel $channel) use ($member) {
            $permissions = $member->getPermissions($channel);
            $special = str_contains($channel->name, 'rules') || str_contains($channel->name, 'welcome');
            return $special && $permissions->view_channel && $permissions->read_message_history;
        });

        foreach ($rulesChannels as $rulesChannel) {
            $this->reactToRulesChannel($rulesChannel);
        }
    }

        private function getSelfGuildMember(Discord $discord, Guild $guild): Member
    {
        return $guild->members->filter(function(Member $member) use ($discord) {
            return $member->username === $discord->user->username;
        })->first();
    }

    private function findPermissions(Discord $discord, Guild $guild): void
    {
        $member = $this->getSelfGuildMember($discord, $guild);

        /** @var Channel $channel */
        foreach ($guild->channels as $channel) {
            $permissions = $member->getPermissions($channel);
            if ($permissions->connect && $permissions->view_channel && $permissions->speak && $channel->members->count()) {
                $this->result->addChannelWithOpenVC($channel->name);
            }
            if ($permissions->send_tts_messages) {
                $this->result->addChannelWithTTS($channel->name);
            }
        }

        if ($this->result->getChannelsWithOpenVC() || $this->result->getChannelsWithTTS()) {
            $discord->getLoop()->stop();
        }
    }

    private function cleanup(): void
    {
        if ($this->guildInfo) {
            $guildId = (int) $this->guildInfo['guild']['id'];
            $this->output->writeln(sprintf('Leaving guild ID %d', $guildId));
            $this->joiner->leave($guildId);
            $this->guildInfo = [];
            $this->result->clear();
        }
    }

    private function performRecon(Discord $discord, string $guildId): void
    {
        $discord->on(Event::GUILD_MEMBER_UPDATE, function (Member $new, Discord $discord, Member $old) {
            if ($new->roles->serialize() === $old->roles->serialize() || $new->username !== $discord->user->username) {
                // roles not being updated for us, bail
                return;
            }
            $this->output->writeln('Roles changed.');
            $this->findPermissions($discord, $new->guild);
        });

        $discord->on(Event::GUILD_CREATE, function (Guild $guild, Discord $discord) {
            $this->reactToRulesChannels($discord, $guild);
            $this->findPermissions($discord, $guild);
        });

        $discord->on(Event::GUILD_DELETE, function (Guild $guild, Discord $discord) {
            $this->output->writeln('Banned from server, stopping event loop.');
            $discord->getLoop()->stop();
        });

        try {
            $this->output->writeln(sprintf('Joining %s.', $guildId));
            $this->guildInfo = $this->joiner->join($guildId);
        } catch (RequestException $e) {
            $this->output->writeln($e->getMessage());
            $discord->getLoop()->stop();
        }
    }

    public function find(string $guildId): Result
    {
        $logger = new Logger('Logger');
        $logger->pushHandler(new NullHandler());

        /** @var Discord $discord */
        $discord = $this->factory->make(
            Discord::class,
            [
                'options' => [
                    'bot' => false,
                    'token' => $this->token,
                    'logger' => $logger
                ]
            ]
        );

        $discord->on('ready', function (Discord $discord) use ($guildId) {
            $this->performRecon($discord, $guildId);
        });

        $loop = $discord->getLoop();
        $loop->addTimer(10, function() use ($loop) {
            /**
             * There is no guarantee that we either:
             * - Find a result immediately.
             * - Get a new role assigned that allows us to find a result.
             *
             * Bail after 10 seconds so we're not stuck infinitely waiting for something to happen.
             */
            $loop->stop();
        });
        $discord->run();

        $result = clone $this->result;
        $this->cleanup();
        return $result;
    }
}
