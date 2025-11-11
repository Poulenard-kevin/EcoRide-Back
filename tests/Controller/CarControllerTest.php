<?php

namespace App\Tests\Controller;

use App\Entity\Car;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CarControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private CarRepository $repository;
    private string $path = '/car/';
    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->repository = $container->get('doctrine')->getRepository(Car::class);
        $this->manager = $container->get('doctrine')->getManager();

        // Nettoyer la base si besoin
        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }
        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Car index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $originalNumObjectsInRepository = count($this->repository->findAll());

        // Créer un utilisateur pour le propriétaire
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('john.doe@example.com');
        $user->setPassword('password'); // Attention : en vrai, hasher le mot de passe
        $this->manager->persist($user);
        $this->manager->flush();

        $crawler = $this->client->request('GET', $this->path . 'new');

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Enregistrer', [
            'car[brand]' => 'TestBrand',
            'car[model]' => 'TestModel',
            'car[color]' => 'Red',
            'car[fuelType]' => 'Electrique',
            'car[registration]' => 'ABC-123',
            'car[seats]' => 4,
            'car[driverPreferences]' => ['Fumeur'], // tableau car champ multiple
            'car[otherPreferences]' => 'Aucune',
            'car[owner]' => $user->getId(), // id de l’utilisateur
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Car();
        $fixture->setBrand('My Title');
        $fixture->setModel('My Title');
        $fixture->setColor('My Title');
        $fixture->setFuelType('My Title');
        $fixture->setRegistration('My Title');
        $fixture->setSeats('My Title');
        $fixture->setDriverPreferences('My Title');
        $fixture->setOtherPreferences('My Title');
        $fixture->setOwner('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Car');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Car();
        $fixture->setBrand('My Title');
        $fixture->setModel('My Title');
        $fixture->setColor('My Title');
        $fixture->setFuelType('My Title');
        $fixture->setRegistration('My Title');
        $fixture->setSeats('My Title');
        $fixture->setDriverPreferences('My Title');
        $fixture->setOtherPreferences('My Title');
        $fixture->setOwner('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'car[brand]' => 'Something New',
            'car[model]' => 'Something New',
            'car[color]' => 'Something New',
            'car[fuelType]' => 'Something New',
            'car[registration]' => 'Something New',
            'car[seats]' => 'Something New',
            'car[driverPreferences]' => 'Something New',
            'car[otherPreferences]' => 'Something New',
            'car[owner]' => 'Something New',
        ]);

        self::assertResponseRedirects('/car/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getBrand());
        self::assertSame('Something New', $fixture[0]->getModel());
        self::assertSame('Something New', $fixture[0]->getColor());
        self::assertSame('Something New', $fixture[0]->getFuelType());
        self::assertSame('Something New', $fixture[0]->getRegistration());
        self::assertSame('Something New', $fixture[0]->getSeats());
        self::assertSame('Something New', $fixture[0]->getDriverPreferences());
        self::assertSame('Something New', $fixture[0]->getOtherPreferences());
        self::assertSame('Something New', $fixture[0]->getOwner());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Car();
        $fixture->setBrand('My Title');
        $fixture->setModel('My Title');
        $fixture->setColor('My Title');
        $fixture->setFuelType('My Title');
        $fixture->setRegistration('My Title');
        $fixture->setSeats('My Title');
        $fixture->setDriverPreferences('My Title');
        $fixture->setOtherPreferences('My Title');
        $fixture->setOwner('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/car/');
    }
}
