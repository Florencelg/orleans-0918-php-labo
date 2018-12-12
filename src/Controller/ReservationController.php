<?php

namespace App\Controller;

use App\Entity\Equipement;
use App\Entity\Reservation;
use App\Entity\ReservationEquipement;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reservation")
 */
class ReservationController extends AbstractController
{
    /**
     * @Route("/", name="reservation_index", methods="GET")
     */
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('reservation/index.html.twig', ['reservations' => $reservationRepository->findAll()]);
    }
    /**
     * @Route("/current", name="current_reservation_index", methods="GET")
     * @param ReservationRepository $reservationRepository
     * @return Response
     */
    public function currentReservationIndex(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getmanager()->getRepository(Reservation::class);
        $reservations = $em->findBy([], ['id'=>'DESC']);

        $result = $paginator->paginate(
            $reservations,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 7)
        );

        return $this->render('reservation/currentReservations.html.twig', [
            'reservations'=> $result,
            ]);
    }
    /**
     * @Route("/new", name="reservation_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $equipements = $em->getRepository(Equipement::class)->findAll();
        $reservation = new Reservation();

        foreach ($equipements as $equipement) {
            $reservationEquipements = new ReservationEquipement();

            $reservationEquipements->setEquipement($equipement);
            $reservationEquipements->setQuantity(0);
            $reservationEquipements->setReservation($reservation);

            $reservation->addReservationEquipement($reservationEquipements);
        }


        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($reservation);
            $em->flush();

            return $this->redirectToRoute('reservation_new');
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'reservationEquipements' =>$reservationEquipements,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="reservation_show", methods="GET")
     */
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', ['reservation' => $reservation]);
    }

    /**
     * @Route("/{id}/edit", name="reservation_edit", methods="GET|POST")
     */
    public function edit(Request $request, Reservation $reservation): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('reservation_index', ['id' => $reservation->getId()]);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="reservation_delete", methods="DELETE")
     */
    public function delete(Request $request, Reservation $reservation): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($reservation);
            $em->flush();
        }

        return $this->redirectToRoute('reservation_index');
    }
}
