<?php

namespace App\Tests\Integration\Booking\Controller;

use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Resource;
use Doctrine\ORM\EntityManagerInterface;
use App\Booking\Domain\Entity\BookedResource;
use Symfony\Component\HttpFoundation\Response;
use App\Booking\Domain\Entity\GroupParticipant;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BookingControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?EntityManagerInterface $entityManager = null;
    private User $owner;
    private User $member;
    private User $nonMember;
    private Group $group;
    private Resource $resource;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Users
        $this->owner = new User();
        $this->owner->setEmail('owner@test.com')->setFullName('Group Owner')->setPassword('password');
        $this->member = new User();
        $this->member->setEmail('member@test.com')->setFullName('Group Member')->setPassword('password');
        $this->nonMember = new User();
        $this->nonMember->setEmail('nonmember@test.com')->setFullName('Non Member')->setPassword('password');
        $this->entityManager->persist($this->owner);
        $this->entityManager->persist($this->member);
        $this->entityManager->persist($this->nonMember);

        // Group
        $this->group = new Group();
        $this->group->setName('Test Group')->setOwner($this->owner);

        // Participants
        $p1 = new GroupParticipant();
        $p1->setUser($this->owner);
        $this->group->addGroupParticipant($p1);
        $p2 = new GroupParticipant();
        $p2->setUser($this->member);
        $this->group->addGroupParticipant($p2);
        $this->entityManager->persist($this->group);

        // Resource
        $this->resource = new Resource();
        $this->resource->setName('Projector')->setQuantity(5)->setGroup($this->group)->setIsActive(true);
        $this->entityManager->persist($this->resource);

        // Booking
        $this->booking = new Booking();
        $this->booking
            ->setGroup($this->group)
            ->setAuthor($this->owner)
            ->setTitle('Test Booking')
            ->setStartAt(new \DateTimeImmutable('2030-01-01 10:00:00'))
            ->setEndAt(new \DateTimeImmutable('2030-01-01 11:00:00'));
        $this->booking->addUser($this->member);
        $br = new BookedResource();
        $br->setResource($this->resource)->setQuantity(1);
        $this->booking->addBookedResource($br);
        $this->entityManager->persist($this->booking);

        $this->entityManager->flush();
    }

    public function test_booking_routes_are_protected_from_anonymous_users(): void
    {
        $groupId = $this->group->getId();
        $bookingId = $this->booking->getId();

        $this->client->catchExceptions(false);
        $this->expectException(AccessDeniedException::class);
        $this->client->request('GET', "/booking-group/{$groupId}/booking/create");
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('POST', "/booking-group/{$groupId}/booking/create");
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('GET', "/booking-group/{$groupId}/booking/update/{$bookingId}");
        $this->assertResponseRedirects('http://localhost/login');

        $this->client->request('DELETE', "/booking-group/{$groupId}/booking/delete/{$bookingId}");
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function test_create_booking_page_access(): void
    {
        // Group member can access
        $this->client->loginUser($this->member);
        $this->client->request('GET', '/booking-group/' . $this->group->getId() . '/booking/create');
        $this->assertResponseIsSuccessful();

        // Non-member cannot access
        $this->client->catchExceptions(false);
        $this->expectException(NotFoundHttpException::class);
        $this->client->loginUser($this->nonMember);
        $this->client->request('GET', '/booking-group/' . $this->group->getId() . '/booking/create');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function test_can_create_booking(): void
    // {
    //     $this->client->loginUser($this->owner);
    //     $crawler = $this->client->request('GET', '/booking-group/' . $this->group->getId() . '/booking/create');

    //     $form = $crawler->selectButton('Create Booking')->form([
    //         'title' => 'New Test Booking',
    //         'description' => 'A description.',
    //         'start_at' => '2030-02-01T10:00',
    //         'end_at' => '2030-02-01T11:00',
    //         'participants' => [$this->member->getId()],
    //         'quantity[' . $this->resource->getId() . ']' => 2,
    //     ]);

    //     $this->client->submit($form);

    //     $this->assertResponseRedirects('/booking-group/' . $this->group->getId());
    //     $this->client->followRedirect();
    //     $this->assertSelectorTextContains('body', 'Booking created successfully!');

    //     $bookingRepo = $this->entityManager->getRepository(Booking::class);
    //     $newBooking = $bookingRepo->findOneBy(['title' => 'New Test Booking']);

    //     $this->assertNotNull($newBooking);
    // }

    public function test_create_booking_with_invalid_data_returns_error(): void
    {
        $this->client->loginUser($this->owner);
        $this->client->request('POST', '/booking-group/' . $this->group->getId() . '/booking/create', [
            'title' => 'Bad Dates',
            'start_at' => '2030-02-01T11:00',
            'end_at' => '2030-02-01T10:00', // End is before start
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorTextContains('body', 'This value should be greater than');
    }

    public function test_get_api_endpoints_work_correctly(): void
    {
        $this->client->loginUser($this->owner);

        // Test get_available_resources
        $this->client->request(
            'POST',
            '/booking-group/' . $this->group->getId() . '/booking/get_available_resources',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['start_at' => '2030-01-01T09:00', 'end_at' => '2030-01-01T10:30']) // Overlaps
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('resources', $response);
        $this->assertEquals(4, $response['resources'][0]['available_quantity']); // 5 total - 1 booked

        // Test get_bookings
        $this->client->request(
            'POST',
            '/booking-group/' . $this->group->getId() . '/booking/get_bookings',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['start_at' => '2030-01-01', 'end_at' => '2030-01-31'])
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response);
        $this->assertEquals($this->booking->getId(), $response[0]['id']);
        $this->assertTrue($response[0]['is_author']);
    }

    public function test_update_page_access(): void
    {
        // Author can access
        $this->client->loginUser($this->owner);
        $this->client->request('GET', '/booking-group/' . $this->group->getId() . '/booking/update/' . $this->booking->getId());
        $this->assertResponseIsSuccessful();

        // Non-author cannot access
        $this->client->catchExceptions(false);
        $this->expectException(NotFoundHttpException::class);
        $this->client->loginUser($this->member);
        $this->client->request('GET', '/booking-group/' . $this->group->getId() . '/booking/update/' . $this->booking->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function test_can_update_booking(): void
    // {
    //     $this->client->loginUser($this->owner);
    //     $crawler = $this->client->request('GET', '/booking-group/' . $this->group->getId() . '/booking/update/' . $this->booking->getId());

    //     $form = $crawler->selectButton('Update Booking')->form(['title' => 'Updated Title']);

    //     $this->client->submit($form);
    //     $this->assertResponseRedirects('/booking-group/' . $this->group->getId());
    //     $this->client->followRedirect();
    //     $this->assertSelectorTextContains('body', 'Booking updated successfully!');

    //     $this->entityManager->clear();
    //     $updatedBooking = $this->entityManager->getRepository(Booking::class)->find($this->booking->getId());
    //     $this->assertEquals('Updated Title', $updatedBooking->getTitle());
    // }

    public function test_delete_booking_access_and_functionality(): void
    {
        // Non-author cannot delete
        $this->client->catchExceptions(false);
        $this->expectException(NotFoundHttpException::class);
        $this->client->loginUser($this->member);
        $this->client->request('DELETE', '/booking-group/' . $this->group->getId() . '/booking/delete/' . $this->booking->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Author can delete
        $bookingId = $this->booking->getId();
        $this->client->loginUser($this->owner);
        $this->client->request('DELETE', '/booking-group/' . $this->group->getId() . '/booking/delete/' . $bookingId);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Booking deleted successfully!', $response['message']);

        $this->entityManager->clear();
        $deletedBooking = $this->entityManager->getRepository(Booking::class)->find($bookingId);
        $this->assertNull($deletedBooking);
    }
}
