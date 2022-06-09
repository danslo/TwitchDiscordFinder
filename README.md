# Twitch Discord Finder

## What is TDF?

TDF stands for Twitch Discord Finder. It's a discord bot that does the following:
- Find twitch streams for provided games / tags.
- Finds the discords associated with those streams.
- Checks the discords for Open VC so you can chat with like-minded individuals.

## Installation

Clone the repository and run `composer install`.

## Usage

### Running the bot

```
php run.php tdf:run --discord-token="<bot token>" --recon-token="<user token>"
```

The bot token is used to listen for commands. See [here](https://github.com/Tyrrrz/DiscordChatExporter/wiki/Obtaining-Token-and-Channel-IDs#how-to-get-a-bot-token) how to get a bot token.

The user token is used to join other discords and figure out permissions. See [here](https://github.com/Tyrrrz/DiscordChatExporter/wiki/Obtaining-Token-and-Channel-IDs#how-to-get-a-user-token) how to get a user token.

Optionally, run without the recon token - this means the bot will not join discord servers and do reconnaissance.

### Using the bot

There are two commands, `!discord` and `!game`.

Examples:

- `!discord english boardgames`
- `!discord programming english anime`
- `!game "The Sims 4" charity`
- `!game "Dead by Daylight" english "playing with viewers"`

## Security

By default, all HTTPS requests are done over TOR. This covers the following requests:
- Joining and leaving Discord servers.
- Accessing the Twitch GraphQL API.

However, Discord websocket communication is a UDP based protocol, so it is not covered by TOR. Therefore, you are still advised to run a VPN.

## Disclaimer

Self-bots are against the Discord TOS. The discord libraries are patched to use regular user agents, but there are no guarantees.

Use at your own risk.
