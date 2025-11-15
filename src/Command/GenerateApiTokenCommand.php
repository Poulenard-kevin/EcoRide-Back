<?php
namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateApiTokenCommand extends Command
{
    protected static $defaultName = 'app:user:generate-token';
    private UserRepository $userRepository;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Génère ou régénère un API token pour un utilisateur')
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln('<error>Utilisateur introuvable</error>');
            return Command::FAILURE;
        }

        $token = bin2hex(random_bytes(32));
        $user->setApiToken($token);
        $this->em->flush();

        $output->writeln('<info>Nouveau token généré :</info> '.$token);
        return Command::SUCCESS;
    }
}