<?php

namespace Cocorico\CoreBundle\Model\Manager;

use Cocorico\CoreBundle\Entity\Booking;
use Cocorico\CoreBundle\Entity\BookingBankWire;
use Cocorico\CoreBundle\Event\BookingBankWireEvent;
use Cocorico\CoreBundle\Event\BookingBankWireEvents;
use Cocorico\CoreBundle\Mailer\TwigSwiftMailer;
use Cocorico\CoreBundle\Repository\BookingBankWireRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BookingBankWireManager extends BaseManager
{
    protected $em;
    protected $checkingSimulation;
    protected $mailer;
    public $maxPerPage;
    protected $dispatcher;

    /**
     * @param EntityManager            $em
     * @param bool                     $checkingSimulation
     * @param TwigSwiftMailer          $mailer
     * @param int                      $maxPerPage
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EntityManager $em,
        $checkingSimulation,
        TwigSwiftMailer $mailer,
        $maxPerPage,
        EventDispatcherInterface $dispatcher
    ) {
        $this->em = $em;
        $this->checkingSimulation = $checkingSimulation;
        $this->mailer = $mailer;
        $this->maxPerPage = $maxPerPage;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param int   $offererId
     * @param int   $page
     * @param array $status
     *
     * @return Paginator
     *
     */
    public function findByOfferer($offererId, $page, $status = array())
    {
        $queryBuilder = $this->getRepository()->getFindByOffererQuery($offererId, $status);

        //Pagination
        $queryBuilder
            ->setFirstResult(($page - 1) * $this->maxPerPage)
            ->setMaxResults($this->maxPerPage);

        //Query
        $query = $queryBuilder->getQuery();

        return new Paginator($query);
    }


    /**
     * @param int   $id
     * @param int   $offererId
     * @param array $status
     *
     * @return BookingBankWire|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByOfferer($id, $offererId, $status = array())
    {
        $queryBuilder = $this->getRepository()->getFindOneByOffererQuery($id, $offererId, $status);

        $query = $queryBuilder->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Create a new bank wire
     *
     * @param Booking $booking
     * @return BookingBankWire
     */
    public function create(Booking $booking)
    {
        $offerer = $booking->getListing()->getUser();
        $amountToPayToOfferer = $booking->getAmountToPayToOfferer();

        $bankWire = new BookingBankWire();
        $bankWire->setBooking($booking);
        $bankWire->setAmount($amountToPayToOfferer);
        $bankWire->setUser($offerer);

        return $bankWire;
    }

    /**
     * Compute the bank wire amount and the remaining amount to wire when a voucher discount has been used for booking
     *
     * @param BookingBankWire $bookingBankWire
     * @return array|null
     */
    public function getAmountAndRemainingAmountToWire(BookingBankWire $bookingBankWire)
    {
        return null;
    }

    /**
     * Check Bookings Bank Wires
     *
     * @return int
     * @throws \Exception
     */
    public function checkBookingsBankWires()
    {
        $result = 0;
        $bookingsBankWiresToCheck = $this->getRepository()->findBookingsBankWiresToCheck();
        foreach ($bookingsBankWiresToCheck as $bookingBankWireToCheck) {
            if ($this->check($bookingBankWireToCheck)) {
                $result++;
            }
        }

        return $result;
    }


    /**
     * Check Bookings Bank Wires:
     *  If the wire has been transferred the status is set to Done.
     *
     * @param BookingBankWire $bookingBankWire
     *
     * @return boolean
     */
    public function check(BookingBankWire $bookingBankWire)
    {
        $event = new BookingBankWireEvent($bookingBankWire);
        $this->dispatcher->dispatch(BookingBankWireEvents::BOOKING_BANK_WIRE_CHECK, $event);

        return $event->getChecked();
    }

    /**
     * @param  BookingBankWire $bookingBankWire
     *
     * @return BookingBankWire
     */
    public function save(BookingBankWire $bookingBankWire)
    {
        $this->persistAndFlush($bookingBankWire);

        return $bookingBankWire;
    }


    /**
     * @return TwigSwiftMailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @return bool
     */
    public function getCheckingSimulation()
    {
        return $this->checkingSimulation;
    }


    /**
     *
     * @return BookingBankWireRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('CocoricoCoreBundle:BookingBankWire');
    }

}
