<?php

namespace Hamlet\Command;

use Exception;
use Hamlet\GoogleDrive\GoogleDriveClientFactory;
use Hamlet\Profile\ProfileCollection;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuthorizeGoogleApplicationCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('google-auth')
            ->setDescription('Authorize Google application profile')
            ->addArgument('profile', InputArgument::REQUIRED, 'Profile name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = new GoogleDriveClientFactory();
        $output->write('Enter Google client ID: ');
        $clientId = trim(fgets(STDIN));
        $output->write('Enter Google client secret: ');
        $clientSecret = trim(fgets(STDIN));

        $client = $factory->getClient($clientId, $clientSecret);

        $url = $client->createAuthUrl();
        $output->writeln('Visit the following URL and copy the code' . PHP_EOL);
        $output->writeln($url . PHP_EOL);
        $output->write('Please enter the code: ');
        $authCode = trim(fgets(STDIN));

        $collection = new ProfileCollection();
        $profileName = $input->getArgument('profile');
        $settings = (array) $collection->getProfile($profileName);
        $settings['google'] = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'accessToken' => json_decode($client->authenticate($authCode)),
        ];
        $collection->setProfile($profileName, $settings);
        $output->writeln("Profile {$profileName} updated");
    }
}