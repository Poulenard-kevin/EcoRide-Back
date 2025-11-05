<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Entity\Covoiturage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestReservationCreationController extends AbstractController
{
    #[Route('/test/create-reservation', name: 'test_create_reservation')]
    public function createReservation(EntityManagerInterface $em): Response
    {
        // Récupérer un utilisateur existant (id = 1)
        $utilisateur = $em->getRepository(Utilisateur::class)->find(1);
        if (!$utilisateur) {
            return new Response('Utilisateur non trouvé', 404);
        }

        // Récupérer un covoiturage existant (id = 1)
        $covoiturage = $em->getRepository(Covoiturage::class)->find(1);
        if (!$covoiturage) {
            return new Response('Covoiturage non trouvé', 404);
        }

        // --- DÉBUT DE LA MODIFICATION ---
        // Assure-toi que les dates n'ont pas d'heure pour les champs de type DATE_MUTABLE
        $dateDepart = (new \DateTime())->setTime(0, 0, 0);
        $heureDepart = (new \DateTime())->setTime(10, 0, 0); // Exemple d'heure
        $dateArrivee = (new \DateTime())->modify('+1 day')->setTime(0, 0, 0);
        $heureArrivee = (new \DateTime())->setTime(12, 0, 0); // Exemple d'heure

        // Mettre à jour le covoiturage avec les dates et heures séparées
        $covoiturage->setDateDepart($dateDepart);
        $covoiturage->setHeureDepart($heureDepart);
        $covoiturage->setDateArrivee($dateArrivee);
        $covoiturage->setHeureArrivee($heureArrivee);
        // --- FIN DE LA MODIFICATION ---

        // Créer une nouvelle réservation
        $reservation = new Reservation();
        $reservation->setPassager($utilisateur);
        $reservation->setCovoiturage($covoiturage);
        $reservation->setNbPlacesReservees(2);
        $reservation->setDateReservation(new \DateTime()); // La date de réservation peut avoir l'heure
        $reservation->setStatut('en attente');

        // Persister et sauvegarder
        $em->persist($reservation);
        $em->flush();

        return new Response('Réservation créée avec succès avec ID : ' . $reservation->getId());
    }
}