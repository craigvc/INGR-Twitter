<?php

namespace App\Command;

use App\Entity\Tweet;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\TweetRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\Dotenv\Dotenv;

class UpdateKeywordsCommand extends Command
{
    protected static $defaultName = 'update:keywords';

    private $u_repo;
    private $t_repo;

    public function __construct(UserRepository $u_repo, TweetRepository $t_repo)
    {
        $this->u_repo = $u_repo;
        $this->t_repo = $t_repo;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $users = $this->u_repo->findAll();
        $io->success($users[0]->getUsername());

        $dotenv = new Dotenv();
        $dotenv->load('/var/www/.env');

//        $url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
        $url = "statuses/user_timeline";
        $connection = new TwitterOAuth($_ENV["CONSUMER_KEY"], $_ENV["CONSUMER_SECRET"], $_ENV["TWITTER_API_ACCESS_TOKEN"], $_ENV["TWITTER_API_ACCESS_TOKEN_SECRET"]);

        $keyword_in_tweets = $connection->get("search/tweets", ["q" => "#ingrproject"]);
//        dd($keyword_in_tweets);
        //keyword_in_tweets contains an array (statuses) of tweets with the specified word, and an object (search_metadata) representing the params sent
//        foreach ($keyword_in_tweets->statuses as $status) {
//            echo "item - ";
//            dump(gettype($status));
//            dump($status->entities->hashtags);
//        }
//        dd($keyword_in_tweets);
        $tweet_count = count($keyword_in_tweets->statuses);

        dd($tweet_count);
        foreach ($users as $user) {
            $tweets = $connection->get($url, ["screen_name" => $user->getTwitterName()]);
            $this->addNewTweets($tweets, $user);
            $this->deleteOldTweets($tweets, $user);
        }

        $io->success('Command Completed');
        return 0;
    }


    private function addNewTweets(array $tweets, User $user)
    {
        foreach ($tweets as $tweet) {
            $tweet_result = $this->t_repo->findOneById($tweet->id);
            if (is_null($tweet_result)) {
                $this->t_repo->insert($tweet->id, $tweet->text, $tweet->created_at, $user->getTwitterName(), $user);
            }
        }
    }

    private function deleteOldTweets(array $tweets, User $user)
    {
        $user_tweets = $this->t_repo->findTweetsByUser($user->getId());
        $tweets_ids = array_map(function ($tweet) {
            return $tweet->id;
        }, $tweets);
        for ($i = 0; $i < count($user_tweets); $i++) {
            $user_tweet_id = $user_tweets[$i]->getTwitterId();
            if (!in_array($user_tweet_id, $tweets_ids)) {
                $this->t_repo->delete($user_tweets[$i]);
            }
        }
    }
}
