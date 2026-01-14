<?php

namespace App\Controller\Mongo;

use App\Service\MongoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReviewMongoController extends AbstractController
{
    /**
     * Liste les avis depuis MongoDB
     */
    #[Route('/api/mongo/reviews', name: 'mongo_reviews_list', methods: ['GET'])]
    public function list(MongoService $mongo): JsonResponse
    {
        $collection = $mongo->getCollection('reviews');

        $cursor = $collection->find([], [
            'sort' => ['createdAt' => -1],
            'limit' => 50
        ]);

        $reviews = [];
        foreach ($cursor as $document) {
            $reviews[] = [
                'id' => (string) ($document['_id'] ?? ''),
                'comment' => $document['comment'] ?? '',
                'note' => $document['note'] ?? 0,
                'createdAt' => isset($document['createdAt']) ? $document['createdAt'] : null,
                'userId' => $document['userId'] ?? null,
            ];
        }

        return new JsonResponse($reviews);
    }

    /**
     * Ajoute un avis dans MongoDB
     */
    #[Route('/api/mongo/reviews', name: 'mongo_reviews_create', methods: ['POST'])]
    public function create(Request $request, MongoService $mongo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['comment']) || !isset($data['note'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        try {
            $collection = $mongo->getCollection('reviews');

            $result = $collection->insertOne([
                'comment'   => (string)$data['comment'],
                'note'      => (int)$data['note'],
                'createdAt' => new \MongoDB\BSON\UTCDateTime(), 
                'userId'    => $this->getUser() ? (int) $this->getUser()->getId() : (isset($data['userId']) ? (int)$data['userId'] : null),
                'source'    => 'NoSQL_MongoDB'
            ]);

            return new JsonResponse([
                'status' => 'Avis enregistré dans MongoDB !',
                'id' => (string)$result->getInsertedId()
            ], 201);

        } catch (\Exception $e) {
            error_log("Erreur MongoDB : " . $e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}