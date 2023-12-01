<?php

namespace App\Services;

use App\Entity\Asset;
use App\Exception\InvalidAssetParameterException;
use App\Repository\AssetRepository;
use App\Repository\UserRepository;
use App\Validator\AssetValidator;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class AssetService
 *
 * Service for managing user assets.
 */
class AssetService
{
    /**
     * AssetService constructor.
     *
     * @param UserRepository $userRepository
     * @param AssetRepository $assetRepository
     * @param AssetValidator $validator
     * @param ExchangeRateService $exchangeRateService
     * @param EntityManagerInterface $em
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AssetRepository $assetRepository,
        private readonly AssetValidator $validator,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly EntityManagerInterface $em,
    )
    { }

    /**
     * Create a new asset for a user.
     *
     * @param array $data Data for creating the asset.
     * @param int $userId User ID.
     *
     * @return Asset The created asset.
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     */
    public function createNewAssetForUser(array $data, int $userId): Asset
    {
        $asset = $this->createAssetFromDataArray($data);

        $user = $this->userRepository->find($userId);
        $user->addAsset($asset);

        $this->em->persist($asset);
        $this->em->flush();

        return $asset;
    }

    /**
     * Get a user's asset by its ID.
     *
     * @param int $userId User ID.
     * @param int $assetId Asset ID.
     *
     * @return Asset|null The asset or null if not found.
     */
    public function getUserAssetById(int $userId, int $assetId): ?Asset
    {
        //loading User model to Doctrine
        $this->userRepository->find($userId);

        return $this->assetRepository->find($assetId);
    }

    /**
     * Update an asset for a user.
     *
     * @param array $data Data for updating the asset.
     * @param int $userId User ID.
     * @param int $assetId Asset ID.
     *
     * @return Asset The updated asset.
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     */
    public function updateAsset(array $data, int $userId, int $assetId): Asset
    {
        $asset = $this->getUserAssetById($userId, $assetId);
        $asset= $this->fillingAsset($asset, $data);

        $this->em->persist($asset);
        $this->em->flush();

        return $asset;
    }


    /**
     * Delete an asset for a user.
     *
     * @param int $assetId Asset ID.
     *
     * @return void
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     */
    public function deleteAsset(int $assetId): void
    {
        $asset = $this->assetRepository->find($assetId);

        if (!$asset) {
            throw new InvalidAssetParameterException('incorrect asset id.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($asset);
        $this->em->flush();
    }


    /**
     * Delete all assets for a user.
     *
     * @param int $userId User ID.
     *
     * @return void
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     */
    public function deleteAllUserAssets(int $userId): void
    {
        $assets = $this->getAllUserAssets($userId);

        foreach ($assets as $asset) {
            $this->em->remove($asset);
        }

        $this->em->flush();
    }


    /**
     * Get the values of a user's assets in a specific currency.
     *
     * @param int $userId User ID.
     * @param string $currency Currency code.
     *
     * @return array An array of asset values in the specified currency.
     *
     * @throws ClientExceptionInterface When a client error occurs during the request.
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     * @throws RedirectionExceptionInterface When the request is redirected.
     * @throws ServerExceptionInterface When a server error occurs during the request.
     * @throws InvalidArgumentException When a transport error occurs during the request.
     */
    public function getUserAssetsValueByCurrency(int $userId, string $currency): array
    {
        $assetValues = [];
        $assets = $this->getAllUserAssets($userId);

        /** @var Asset $asset */
        foreach ($assets as $asset) {
            $assetValueInCurrency = $this->exchangeRateService->getExchangeRate($asset->getCurrency(), $currency);

            isset($assetValues[$asset->getLabel()]) ?
                $assetValues[$asset->getLabel()][$asset->getCurrency()] = $assetValueInCurrency * $asset->getValue() :
                $assetValues[$asset->getLabel()][$asset->getCurrency()] = $assetValueInCurrency;
        }

        return $assetValues;
    }


    /**
     * Get the total value of a user's assets in a specific currency.
     *
     * @param int $userId User ID.
     * @param string $currency Currency code.
     *
     * @return float|int The total value of the user's assets in the specified currency.
     *
     * @throws ClientExceptionInterface When a client error occurs during the request.
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     * @throws RedirectionExceptionInterface When the request is redirected.
     * @throws ServerExceptionInterface When a server error occurs during the request.
     * @throws InvalidArgumentException When a transport error occurs during the request.
     */
    public function getTotalValueUserAssetByCurrency(int $userId, string $currency): float|int
    {
        $assetValues = $this->getUserAssetsValueByCurrency($userId, $currency);

        $sum = array_sum($assetValues);
        foreach($assetValues as $assetValue) {
            $sum += is_array($assetValue) ? array_sum($assetValue) : 0;
        }
        return $sum;
    }


    /**
     * Get all assets belonging to a user.
     *
     * @param int $userId User ID.
     *
     * @return Collection A collection of user's assets.
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     */
    public function getAllUserAssets(int $userId): Collection
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new InvalidAssetParameterException('incorrect user id.', Response::HTTP_NOT_FOUND);
        }

        return $user->getAssets();
    }

    /**
     * Create an Asset instance from an array of data.
     *
     * @param array $data Data for creating the asset.
     *
     * @return Asset The created asset.
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     */
    private function createAssetFromDataArray(array $data): Asset
    {
        $asset = new Asset();

        return $this->fillingAsset($asset, $data);
    }

    /**
     * Fill an Asset object with data from an array.
     *
     * @param Asset $asset The asset object to fill.
     * @param array $data Data to fill the asset with.
     *
     * @return Asset The filled asset.
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid.
     */
    private function fillingAsset(Asset $asset, array $data): Asset
    {
        foreach ($data as $key => $value) {
            $methodName = 'set' . ucfirst($key);
            if (method_exists($asset, $methodName)) {
                $asset->$methodName($value);
            }
        }

        $this->validator->validate($asset);

        return $asset;
    }
}