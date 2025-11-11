<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $repository;
    private EntityManagerInterface $manager;
    private string $path = '/user/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->repository = $container->get('doctrine')->getRepository(User::class);
        $this->manager = $container->get('doctrine')->getManager();

        // Nettoyer la base avant chaque test
        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }
        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User index');
    }

    public function testNew(): void
    {
        $originalCount = count($this->repository->findAll());

        $crawler = $this->client->request('GET', $this->path . 'new');

        self::assertResponseStatusCodeSame(200);

        $form = $crawler->selectButton('Save')->form();

        $form['user[lastName]'] = 'TestLastName';
        $form['user[firstName]'] = 'TestFirstName';
        $form['user[email]'] = 'testuser@example.com';
        $form['user[password]'] = 'password123';
        $form['user[role]'] = 'ROLE_USER';
        $form['user[averageRating]'] = 4.5;
        $form['user[about]'] = 'Test about user';
        $form['user[lastPasswordResetRequestAt]'] = (new \DateTime())->format('Y-m-d H:i:s');

        $this->client->submit($form);

        self::assertResponseRedirects($this->path);

        $newCount = count($this->repository->findAll());
        self::assertSame($originalCount + 1, $newCount);
    }

    public function testShow(): void
    {
        $user = new User();
        $user->setLastName('ShowLastName');
        $user->setFirstName('ShowFirstName');
        $user->setEmail('showuser@example.com');
        $user->setPassword('password123');
        $user->setRoles([User::ROLE_USER]);
        $user->setAverageRating(3.7);
        $user->setAbout('About show user');
        $user->setLastPasswordResetRequestAt(new \DateTime());

        $this->manager->persist($user);
        $this->manager->flush();

        $this->client->request('GET', $this->path . $user->getId());

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User');
    }

    public function testEdit(): void
    {
        $user = new User();
        $user->setLastName('OldLastName');
        $user->setFirstName('OldFirstName');
        $user->setEmail('edituser@example.com');
        $user->setPassword('password123');
        $user->setRoles([User::ROLE_USER]);
        $user->setAverageRating(2.5);
        $user->setAbout('Old about');
        $user->setLastPasswordResetRequestAt(new \DateTime());

        $this->manager->persist($user);
        $this->manager->flush();

        $crawler = $this->client->request('GET', $this->path . $user->getId() . '/edit');

        self::assertResponseStatusCodeSame(200);

        $form = $crawler->selectButton('Update')->form();

        $form['user[lastName]'] = 'NewLastName';
        $form['user[firstName]'] = 'NewFirstName';
        $form['user[email]'] = 'newemail@example.com';
        $form['user[password]'] = 'newpassword123';
        $form['user[role]'] = 'ROLE_ADMIN';
        $form['user[averageRating]'] = 4.8;
        $form['user[about]'] = 'New about text';
        $form['user[lastPasswordResetRequestAt]'] = (new \DateTime())->format('Y-m-d H:i:s');

        $this->client->submit($form);

        self::assertResponseRedirects($this->path);

        $updatedUser = $this->repository->find($user->getId());

        self::assertSame('NewLastName', $updatedUser->getLastName());
        self::assertSame('NewFirstName', $updatedUser->getFirstName());
        self::assertSame('newemail@example.com', $updatedUser->getEmail());
        self::assertSame('newpassword123', $updatedUser->getPassword());
        self::assertSame('ROLE_ADMIN', $updatedUser->getRoles());
        self::assertEquals(4.8, $updatedUser->getAverageRating());
        self::assertSame('New about text', $updatedUser->getAbout());
        self::assertInstanceOf(\DateTimeInterface::class, $updatedUser->getLastPasswordResetRequestAt());
    }

    public function testRemove(): void
    {
        $originalCount = count($this->repository->findAll());

        $user = new User();
        $user->setLastName('DeleteLastName');
        $user->setFirstName('DeleteFirstName');
        $user->setEmail('deleteuser@example.com');
        $user->setPassword('password123');
        $user->setRoles([User::ROLE_USER]);
        $user->setAverageRating(1.0);
        $user->setAbout('About to delete');
        $user->setLastPasswordResetRequestAt(new \DateTime());

        $this->manager->persist($user);
        $this->manager->flush();

        self::assertSame($originalCount + 1, count($this->repository->findAll()));

        $crawler = $this->client->request('GET', $this->path . $user->getId());

        $form = $crawler->selectButton('Delete')->form();
        $this->client->submit($form);

        self::assertSame($originalCount, count($this->repository->findAll()));
        self::assertResponseRedirects($this->path);
    }

    // Tests complémentaires

    public function testNewInvalidEmail(): void
    {
        $crawler = $this->client->request('GET', $this->path . 'new');

        $form = $crawler->selectButton('Save')->form();

        $form['user[lastName]'] = 'Test';
        $form['user[firstName]'] = 'User';
        $form['user[email]'] = 'invalid-email'; // Email invalide
        $form['user[password]'] = 'Password123!';
        $form['user[role]'] = 'ROLE_USER';

        $this->client->submit($form);

        // Vérifie que la page ne redirige pas (erreur de validation)
        self::assertResponseStatusCodeSame(200);

        // Vérifie la présence d’un message d’erreur lié à l’email
        self::assertSelectorTextContains('.form-error-message', 'L\'email n\'est pas valide');
    }

    public function testAccessDeniedForAnonymous(): void
    {
        // Déconnecte le client si connecté
        $this->client->restart();

        $this->client->request('GET', $this->path);

        // Vérifie la redirection vers la page de login
        self::assertResponseRedirects('/login');
    }

    public function testEditInvalidData(): void
    {
        // Crée un utilisateur valide
        $user = new User();
        $user->setLastName('Valid');
        $user->setFirstName('User');
        $user->setEmail('valid@example.com');
        $user->setPassword('Password123!');
        $user->setRoles([User::ROLE_USER]);
        $this->manager->persist($user);
        $this->manager->flush();

        $crawler = $this->client->request('GET', $this->path . $user->getId() . '/edit');

        $form = $crawler->selectButton('Update')->form();

        $form['user[email]'] = 'invalid-email';

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.form-error-message', 'L\'email n\'est pas valide');
    }

    public function testDeleteNonExistentUser(): void
    {
        $this->client->request('POST', $this->path . '999999/delete', [
            '_token' => $this->getCsrfToken('delete999999'),
        ]);

        // Vérifie que la réponse est 404 ou redirection avec message d’erreur
        self::assertResponseStatusCodeSame(404);
    }

    public function testUserListContainsCreatedUser(): void
    {
        $user = new User();
        $user->setLastName('List');
        $user->setFirstName('User');
        $user->setEmail('listuser@example.com');
        $user->setPassword('Password123!');
        $user->setRoles([User::ROLE_USER]);
        $this->manager->persist($user);
        $this->manager->flush();

        $crawler = $this->client->request('GET', $this->path);

        self::assertSelectorTextContains('table', 'listuser@example.com');
    }
}