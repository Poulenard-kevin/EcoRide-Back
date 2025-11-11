<?php

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private BookingRepository $repository;
    private string $path = '/booking/';
    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = static::getContainer()->get('doctrine')->getRepository(Booking::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Booking index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $originalNumObjectsInRepository = count($this->repository->findAll());

        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'booking[bookingDate]' => 'Testing',
            'booking[reservedSeats]' => 'Testing',
            'booking[status]' => 'Testing',
            'booking[passenger]' => 'Testing',
            'booking[carpool]' => 'Testing',
        ]);

        self::assertResponseRedirects('/booking/');

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Booking();
        $fixture->setbookingDate('My Title');
        $fixture->setReservedSeats('My Title');
        $fixture->setStatus('My Title');
        $fixture->setPassenger('My Title');
        $fixture->setCarpool('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Booking');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Booking();
        $fixture->setbookingDate('My Title');
        $fixture->setReservedSeats('My Title');
        $fixture->setStatus('My Title');
        $fixture->setPassenger('My Title');
        $fixture->setCarpool('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'booking[bookingDate]' => 'Something New',
            'booking[reservedSeats]' => 'Something New',
            'booking[status]' => 'Something New',
            'booking[passenger]' => 'Something New',
            'booking[carpool]' => 'Something New',
        ]);

        self::assertResponseRedirects('/booking/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getbookingDate());
        self::assertSame('Something New', $fixture[0]->getReservedSeats());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getPassenger());
        self::assertSame('Something New', $fixture[0]->getCarpool());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();

        $originalNumObjectsInRepository = count($this->repository->findAll());

        $fixture = new Booking();
        $fixture->setbookingDate('My Title');
        $fixture->setReservedSeats('My Title');
        $fixture->setStatus('My Title');
        $fixture->setPassenger('My Title');
        $fixture->setCarpool('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        self::assertSame($originalNumObjectsInRepository + 1, count($this->repository->findAll()));

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertSame($originalNumObjectsInRepository, count($this->repository->findAll()));
        self::assertResponseRedirects('/booking/');
    }
}
