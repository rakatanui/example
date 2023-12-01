<?php

namespace App\Controller;

use App\Exception\InvalidAssetParameterException;
use App\Services\AssetService;
use App\Services\ExchangeRateService;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class UserAssetsController
 *
 * Controller for managing user assets.
 */
#[Route('/api/v1/users/{userId}')]
class UserAssetsController extends AbstractController
{
    /**
     * UserAssetsController constructor.
     *
     * @param AssetService         $assetService The asset service.
     * @param SerializerInterface  $serializer   The serializer for data normalization.
     */
    public function __construct(
        private readonly AssetService $assetService,
        private readonly SerializerInterface $serializer,
    )
    {}

    /**
     * Create a new asset for the user.
     *
     * @param Request $request The HTTP request.
     * @param int     $userId   The user's ID.
     *
     * @return Response
     */
    #[Route('/assets', name: 'create_user_asset', methods: [Request::METHOD_POST])]
    public function createAsset(Request $request, int $userId): Response
    {
        $data = $request->request->all();

        try {
            $asset = $this->assetService->createNewAssetForUser($data, $userId);
        } catch (InvalidAssetParameterException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
        }

        return new JsonResponse($this->normalize($asset), Response::HTTP_CREATED);
    }

    /**
     * Get information about a user's asset.
     *
     * @param int $userId   The user's ID.
     * @param int $assetId  The asset's ID.
     *
     * @return JsonResponse
     */
    #[Route('/assets/{assetId}', name: 'get_user_asset', methods: [Request::METHOD_GET])]
    public function getAsset(int $userId, int $assetId): JsonResponse
    {
        $asset = $this->assetService->getUserAssetById($userId, $assetId);

        if (!$asset) {
            return new JsonResponse($asset, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->normalize($asset));
    }

    /**
     * Update a user's asset.
     *
     * @param Request $request   The HTTP request.
     * @param int     $userId    The user's ID.
     * @param int     $assetId   The asset's ID.
     *
     * @return JsonResponse
     */
    #[Route('/assets/{assetId}', name: 'update_user_asset', methods: [Request::METHOD_PUT])]
    public function updateAsset(Request $request, int $userId, int $assetId): JsonResponse
    {
        $data = $request->request->all();

        try {
            $asset = $this->assetService->updateAsset($data, $userId, $assetId);
        } catch (InvalidAssetParameterException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
        }
        return new JsonResponse($this->normalize($asset));
    }

    /**
     * Delete a user's asset.
     *
     * @param int $userId   The user's ID.
     * @param int $assetId  The asset's ID.
     *
     * @return JsonResponse
     */
    #[Route('/assets/{assetId}', name: 'delete_user_asset', methods: [Request::METHOD_DELETE])]
    public function deleteAsset(int $userId, int $assetId): JsonResponse
    {
        try {
            $this->assetService->deleteAsset($assetId);
        } catch (InvalidAssetParameterException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Get all user's assets.
     *
     * @param int $userId   The user's ID.
     *
     * @return JsonResponse
     */
    #[Route('/assets', name: 'get_all_user_assets', methods: [Request::METHOD_GET])]
    public function getAllUserAssets(int $userId): JsonResponse
    {
        try {
            $assets = $this->assetService->getAllUserAssets($userId);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
        }

        return new JsonResponse($this->normalize($assets));
    }

    /**
     * Delete all user's assets.
     *
     * @param int $userId   The user's ID.
     *
     * @return JsonResponse
     */
    #[Route('/assets', name: 'delete_all_user_assets', methods: [Request::METHOD_DELETE])]
    public function deleteAllUserAssets(int $userId): JsonResponse
    {
        try {
            $this->assetService->deleteAllUserAssets($userId);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the total value of user's assets in USD.
     *
     * @param  int  $userId  The user's ID.
     *
     * @return JsonResponse
     *
     * @throws InvalidArgumentException
     */
    #[Route('/assets-total-values', name: 'get_total_assets_value_usd', methods: [Request::METHOD_GET])]
    public function getTotalValueUserAssetsInUSD(int $userId): JsonResponse
    {
        try {
            $total = $this->assetService->getTotalValueUserAssetByCurrency($userId, ExchangeRateService::USD_CURRENCY);
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
        }

        return new JsonResponse($total);
    }

    /**
     * Get separate values of user's assets in USD.
     *
     * @param  int  $userId  The user's ID.
     *
     * @return JsonResponse
     *
     * @throws InvalidArgumentException
     */
    #[Route('/assets-separate-values', name: 'get_separate_assets_value_usd', methods: [Request::METHOD_GET])]
    public function getSeparateValueUserAssetsInUSD(int $userId): JsonResponse
    {
        try {
            $separateValues = $this->assetService->getUserAssetsValueByCurrency($userId, ExchangeRateService::USD_CURRENCY);
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode());
        }

        return new JsonResponse($separateValues);
    }

    /**
     * Normalize an object for JSON response.
     *
     * @param object $normalizationObject The object to normalize.
     *
     * @return array
     */
    private function normalize(object $normalizationObject): array
    {
        return $this->serializer->normalize($normalizationObject, JsonEncoder::FORMAT, [
        'circular_reference_handler' => function ($object) {
            return $object->getId();
        }]);
    }
}