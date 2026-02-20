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
        try {
            $collection = $mongo->getCollection('reviews');

            $cursor = $collection->find([], [
                'sort' => ['createdAt' => -1],
                'limit' => 50
            ]);

            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = [
                    'id'        => (string) ($document['_id'] ?? ''),
                    'comment'   => $document['comment'] ?? '',
                    'note'      => $document['note'] ?? 0,
                    'createdAt' => isset($document['createdAt']) ? (string) $document['createdAt'] : null,
                    'userId'    => $document['userId'] ?? null,
                ];
            }

            return $this->json($reviews);

        } catch (\Throwable $e) {
            error_log('MONGO LIST ERROR: ' . $e->getMessage());
            return $this->json([
                'error'   => 'mongo_list_failed',
                'message' => $e->getMessage(),
                'type'    => get_class($e),
            ], 500);
        }
    }

    /**
     * Ajoute un avis dans MongoDB
     */
    #[Route('/api/mongo/reviews', name: 'mongo_reviews_create', methods: ['POST'])]
    public function create(Request $request, MongoService $mongo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'invalid_json'], 400);
        }

        if (!isset($data['comment']) || !isset($data['note'])) {
            return $this->json(['error' => 'Données manquantes (note ou comment)'], 400);
        }

        try {
            $collection = $mongo->getCollection('reviews');

            $document = [
                'comment'       => (string) $data['comment'],
                'note'          => (int) $data['note'],
                'createdAt'     => new \MongoDB\BSON\UTCDateTime(),
                'source'        => 'NoSQL_MongoDB',
                'reservationId' => isset($data['reservationId']) ? (string) $data['reservationId'] : null,
                'carpoolId'     => isset($data['carpoolId'])     ? (string) $data['carpoolId']     : null,
            ];

            // Gestion du userId : priorité à l'utilisateur connecté
            $user = $this->getUser();
            if ($user) {
                $document['userId'] = (int) $user->getId();
            } elseif (isset($data['userId']) && $data['userId'] !== null) {
                $document['userId'] = (int) $data['userId'];
            } else {
                $document['userId'] = null;
            }

            error_log('MONGO INSERT document: ' . json_encode($document));

            $result = $collection->insertOne($document);

            return $this->json([
                'status' => 'Avis enregistré dans MongoDB !',
                'id'     => (string) $result->getInsertedId(),
            ], 201);

        } catch (\Throwable $e) {
            error_log('CRITICAL MONGO ERROR: ' . $e->getMessage());
            error_log('MONGO TRACE: ' . $e->getTraceAsString());

            return $this->json([
                'error'   => 'mongo_insert_failed',
                'message' => $e->getMessage(),
                'type'    => get_class($e),
            ], 500);
        }
    }

    /**
     * Patch un avis MongoDB pour lier l'ID SQL
     */
    #[Route('/api/mongo/reviews/{id}', name: 'mongo_reviews_patch', methods: ['PATCH'])]
    public function patch(string $id, Request $request, MongoService $mongo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'invalid_json'], 400);
        }

        try {
            $collection = $mongo->getCollection('reviews');

            $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['$set' => ['sqlId' => $data['sqlId'] ?? null]]
            );

            return $this->json(['status' => 'updated']);

        } catch (\Throwable $e) {
            error_log('MONGO PATCH ERROR: ' . $e->getMessage());
            return $this->json([
                'error'   => 'mongo_patch_failed',
                'message' => $e->getMessage(),
                'type'    => get_class($e),
            ], 500);
        }
    }
}