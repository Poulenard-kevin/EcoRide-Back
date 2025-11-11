<?php

namespace App\Tests\Controller;

use App\Entity\Carpool;
use App\Repository\CarpoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CarpoolControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private CarpoolRepository $repository;
    private string $path = '/carpool/';
    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->repository = $container->get('doctrine')->getRepository(Carpool::class);
        $this->manager = $container->get('doctrine')->getManager();

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }
        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Carpool index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
{
    $originalNumObjectsInRepository = count($this->repository->findAll());

    $container = static::getContainer();
    $em = $container->get('doctrine')->getManager();

    // Créer un utilisateur pour le chauffeur
    $user = new User();
    $user->setFirstName('John');
    $user->setLastName('Doe');
    $user->setEmail('john.doe@example.com');
    $user->setPassword('password'); // Hasher en vrai
    $em->persist($user);

    // Créer une voiture
    $car = new Car();
    $car->setBrand('Toyota');
    $car->setModel('Corolla');
    $car->setColor('Blue');
    $car->setFuelType('Thermique');
    $car->setRegistration('XYZ-123');
    $car->setSeats(4);
    $car->setOwner($user);
    $em->persist($car);

    $em->flush();

    $crawler = $this->client->request('GET', $this->path . 'new');

    self::assertResponseStatusCodeSame(200);

    $this->client->submitForm('Enregistrer', [
        'carpool[departureDate]' => '2025-12-01',
        'carpool[departureTime]' => '08:00',
        'carpool[departureLocation]' => 'Paris',
        'carpool[arrivalDate]' => '2025-12-01',
        'carpool[arrivalTime]' => '12:00',
        'carpool[arrivalLocation]' => 'Lyon',
        'carpool[pricePerSeat]' => 20,
        'carpool[totalSeats]' => 4,
        'carpool[availableSeats]' => 3,
        'carpool[status]' => Carpool::STATUS_ACTIVE, // ou la valeur correspondante
        'carpool[driver]' => $user->getId(),
        'carpool[car]' => $car->getId(),
    ]);

    self::assertResponseRedirects($this->path);

    self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
}

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Carpool();
        $fixture->setDepartureDate('My Title');
        $fixture->setDepartureTime('My Title');
        $fixture->setDepartureLocation('My Title');
        $fixture->setArrivalDate('My Title');
        $fixture->setArrivalTime('My Title');
        $fixture->setArrivalLocation('My Title');
        $fixture->setPricePerSeat('My Title');
        $fixture->setTotalSeats('My Title');
        $fixture->setAvailableSeats('My Title');
        $fixture->setStatus('My Title');
        $fixture->setDriver('My Title');
        $fixture->setCar('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Carpool');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Carpool();
        $fixture->setDepartureDate('My Title');
        $fixture->setDepartureTime('My Title');
        $fixture->setDepartureLocation('My Title');
        $fixture->setArrivalDate('My Title');
        $fixture->setArrivalTime('My Title');
        $fixture->setArrivalLocation('My Title');
        $fixture->setPricePerSeat('My Title');
        $fixture->setTotalSeats('My Title');
        $fixture->setAvailableSeats('My Title');
        $fixture->setStatus('My Title');
        $fixture->setDriver('My Title');
        $fixture->setCar('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'carpool[departureDate]' => 'Something New',
            'carpool[departureTime]' => 'Something New',
            'carpool[departureLocation]' => 'Something New',
            'carpool[arrivalDate]' => 'Something New',
            'carpool[arrivalTime]' => 'Something New',
            'carpool[arrivalLocation]' => 'Something New',
            'carpool[pricePerSeat]' => 'Something New',
            'carpool[totalSeats]' => 'Something New',
            'carpool[availableSeats]' => 'Something New',
            'carpool[status]' => 'Something New',
            'carpool[driver]' => 'Something New',
            'carpool[car]' => 'Something New',
        ]);

        self::assertResponseRedirects('/carpool/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getDepartureDate());
        self::assertSame('Something New', $fixture[0]->getDepartureTime());
        self::assertSame('Something New', $fixture[0]->getDepartureLocation());
        self::assertSame('Something New', $fixture[0]->getArrivalDate());
        self::assertSame('Something New', $fixture[0]->getArrivalTime());
        self::assertSame('Something New', $fixture[0]->getArrivalLocation());
        self::assertSame('Something New', $fixture[0]->getPricePerSeat());
        self::assertSame('Something New', $fixture[0]->getTotalSeats());
        self::assertSame('Something New', $fixture[0]->getAvailableSeats());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getDriver());
        self::assertSame('Something New', $fixture[0]->getCar());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Carpool();
        $fixture->setDepartureDate('My Title');
        $fixture->setDepartureTime('My Title');
        $fixture->setDepartureLocation('My Title');
        $fixture->setArrivalDate('My Title');
        $fixture->setArrivalTime('My Title');
        $fixture->setArrivalLocation('My Title');
        $fixture->setPricePerSeat('My Title');
        $fixture->setTotalSeats('My Title');
        $fixture->setAvailableSeats('My Title');
        $fixture->setStatus('My Title');
        $fixture->setDriver('My Title');
        $fixture->setCar('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/carpool/');
    }
}
