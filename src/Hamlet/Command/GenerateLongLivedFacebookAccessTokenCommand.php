<?php

namespace Hamlet\Command;

use Hamlet\Profile\ProfileCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLongLivedFacebookAccessTokenCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('facebook-auth')
            ->setDescription('Generate long lived Facebook access token')
            ->addArgument('profile', InputArgument::REQUIRED, 'Profile name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('<question>Enter Facebook app ID: </question>');
        $appId = trim(fgets(STDIN));
        $output->write('<question>Enter Facebook app secret: </question>');
        $appSecret = trim(fgets(STDIN));
        $output->write('<question>Enter comma separated list of permissions: </question>');
        $scope = trim(fgets(STDIN));
        $output->write('<question>Enter the URL of your application: </question>');
        $appUrl = trim(fgets(STDIN));


        $authorizationUrl = "https://www.facebook.com/dialog/oauth?client_id={$appId}&redirect_uri={$appUrl}&response_type=token&scope={$scope}";
        $output->writeln('Log in with Facebook and visit the following URL');
        $output->writeln($authorizationUrl);

        $output->write('<question>Enter the short-lived token passed to you through URL: </question>');
        $shortLivedToken = trim(fgets(STDIN));

        $tokenExchangeUrl = "https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id={$appId}&client_secret={$appSecret}&fb_exchange_token={$shortLivedToken}";
        $output->writeln('Visit the following URL');
        $output->writeln($tokenExchangeUrl);

        $output->write('<question>Enter the long-lived token passed to you on the page: </question>');
        $longLivedToken = trim(fgets(STDIN));

        $collection = new ProfileCollection();
        $profileName = $input->getArgument('profile');
        $settings = (array) $collection->getProfile($profileName);
        $settings['facebook'] = [
            'appId' => $appId,
            'appSecret' => $appSecret,
            'accessToken' => $longLivedToken,
        ];
        $collection->setProfile($profileName, $settings);
        $output->writeln("Profile {$profileName} updated");
    }
}