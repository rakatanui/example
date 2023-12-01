<?php

namespace App\Tests\Services;

use App\Entity\Asset;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Repository\UserRepository;
use App\Services\AssetService;
use App\Services\ExchangeRateService;
use App\Validator\AssetValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AssetServiceTest extends TestCase
{
    private UserRepository $mockUserRepository;
    private AssetRepository $mockAssetRepository;
    private ExchangeRateService $mockExchangeRateService;
    private EntityManagerInterface $mockEm;
    private AssetService $assetService;

    protected function setUp(): void
    {
        $this->mockUserRepository = $this->createMock(UserRepository::class);
        $this->mockAssetRepository = $this->createMock(AssetRepository::class);
        $validator = $this->createMock(AssetValidator::class);
        $this->mockExchangeRateService = $this->createMock(ExchangeRateService::class);
        $this->mockEm = $this->createMock(EntityManagerInterface::class);

        $this->assetService = new AssetService($this->mockUserRepository, $this->mockAssetRepository,
            $validator, $this->mockExchangeRateService, $this->mockEm);
    }

    public function testCreateNewAssetForUser()
    {
        // Arrange
        $assetArray = [
            'label' => 'binance',
            'currency' => 'IOTA',
            'value' => 2,
        ];
        $expectedAsset = new Asset();
        $expectedAsset->setLabel($assetArray['label']);
        $expectedAsset->setCurrency($assetArray['currency']);
        $expectedAsset->setValue($assetArray['value']);

        $user = new User();
        $user->setId(123);
        $user->addAsset($expectedAsset);

        $this->mockUserRepository->expects($this->once())->method('find')->willReturn($user);
        $this->mockEm->expects($this->once())->method('persist');
        $this->mockEm->expects($this->once())->method('flush');

        // Act
        $resultAsset = $this->assetService->createNewAssetForUser($assetArray, 123);

        // Assert
        $this->assertEquals($expectedAsset, $resultAsset);
    }

    public function testGetUserAssetById()
    {
        // Arrange
        $assetArray = [
            'label' => 'binance',
            'currency' => 'IOTA',
            'value' => 2,
        ];
        $expectedAsset = new Asset();
        $expectedAsset->setLabel($assetArray['label']);
        $expectedAsset->setCurrency($assetArray['currency']);
        $expectedAsset->setValue($assetArray['value']);

        $user = new User();
        $user->setId(123);
        $user->addAsset($expectedAsset);

        $this->mockUserRepository->expects($this->once())->method('find')->willReturn($user);
        $this->mockAssetRepository->expects($this->once())->method('find')->willReturn($expectedAsset);

        // Act
        $resultAsset = $this->assetService->getUserAssetById(123, 456);

        // Assert
        $this->assertEquals($expectedAsset, $resultAsset);
    }

    public function testUpdateAsset()
    {
        // Arrange
        $assetArray = [
            'label' => 'binance',
            'currency' => 'IOTA',
            'value' => 2,
        ];
        $oldAsset = new Asset();
        $oldAsset->setLabel($assetArray['label']);
        $oldAsset->setCurrency($assetArray['currency']);
        $oldAsset->setValue($assetArray['value']);

        $updateData = ['value' => 25];

        $this->mockUserRepository->expects($this->once())->method('find');
        $this->mockAssetRepository->expects($this->once())->method('find')->willReturn($oldAsset);
        $this->mockEm->expects($this->once())->method('persist');
        $this->mockEm->expects($this->once())->method('flush');

        // Act
        $resultAsset = $this->assetService->updateAsset($updateData, 123, 456);

        // Assert
        $this->assertEquals($updateData['value'], $resultAsset->getValue());
    }

    public function testDeleteAsset()
    {
        // Arrange
        $asset = new Asset();
        $this->mockAssetRepository->expects($this->once())->method('find')->willReturn($asset);
        $this->mockEm->expects($this->once())->method('remove');
        $this->mockEm->expects($this->once())->method('flush');

        // Act
        $this->assetService->deleteAsset(123);
    }

    public function testDeleteAllUserAssets()
    {
        // Arrange
        $mockCollection = new ArrayCollection([new Asset(), new Asset(), new Asset()]);

        $mockUser = $this->createMock(User::class);
        $mockUser->expects($this->once())
            ->method('getAssets')
            ->willReturn($mockCollection);

        $this->mockUserRepository->expects($this->once())->method('find')->willReturn($mockUser);
        $this->mockEm->expects($this->exactly(3))->method('remove');
        $this->mockEm->expects($this->once())->method('flush');

        // Act
        $this->assetService->deleteAllUserAssets(123);
    }

    public function testGetUserAssetsValueByCurrency()
    {
        // Arrange
        $assetArray = [
            [
                'label' => 'binance',
                'currency' => 'IOTA',
                'value' => 2,
            ],
            [
                'label' => 'binance',
                'currency' => 'BTC',
                'value' => 20,
            ]
        ];

        $firstAsset = new Asset();
        $firstAsset->setLabel($assetArray[0]['label']);
        $firstAsset->setCurrency($assetArray[0]['currency']);
        $firstAsset->setValue($assetArray[0]['value']);

        $secondAsset = new Asset();
        $secondAsset->setLabel($assetArray[1]['label']);
        $secondAsset->setCurrency($assetArray[1]['currency']);
        $secondAsset->setValue($assetArray[1]['value']);

        $mockCollection = new ArrayCollection([$firstAsset, $secondAsset]);

        $mockUser = $this->createMock(User::class);
        $mockUser->expects($this->once())
            ->method('getAssets')
            ->willReturn($mockCollection);

        $this->mockUserRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($mockUser);

        $this->mockExchangeRateService->expects($this->exactly(2))
            ->method('getExchangeRate')
            ->willReturnMap([
                    ['IOTA', 'USD', 24.4],
                    ['BTC', 'USD', 2000500],
                ]);

        $expectedResult = [
            'binance' => [
                'IOTA' => 24.4,
                'BTC' => 40010000
            ]
        ];

        // Act
        $result = $this->assetService->getUserAssetsValueByCurrency(123, ExchangeRateService::USD_CURRENCY);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetTotalValueUserAssetByCurrency()
    {
        // Arrange
        $assetArray = [
            [
                'label' => 'binance',
                'currency' => 'IOTA',
                'value' => 2,
            ],
            [
                'label' => 'binance',
                'currency' => 'BTC',
                'value' => 20,
            ]
        ];

        $firstAsset = new Asset();
        $firstAsset->setLabel($assetArray[0]['label']);
        $firstAsset->setCurrency($assetArray[0]['currency']);
        $firstAsset->setValue($assetArray[0]['value']);

        $secondAsset = new Asset();
        $secondAsset->setLabel($assetArray[1]['label']);
        $secondAsset->setCurrency($assetArray[1]['currency']);
        $secondAsset->setValue($assetArray[1]['value']);

        $mockCollection = new ArrayCollection([$firstAsset, $secondAsset]);

        $mockUser = $this->createMock(User::class);
        $mockUser->expects($this->once())
            ->method('getAssets')
            ->willReturn($mockCollection);

        $this->mockUserRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($mockUser);

        $this->mockExchangeRateService->expects($this->exactly(2))
            ->method('getExchangeRate')
            ->willReturnMap([
                ['IOTA', 'USD', 24.4],
                ['BTC', 'USD', 2000500],
            ]);

        $expectedResult = 40010024.4;

        // Act
        $result = $this->assetService->getTotalValueUserAssetByCurrency(123, ExchangeRateService::USD_CURRENCY);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetAllUserAssets()
    {
        // Arrange
        $mockCollection = new ArrayCollection([new Asset(), new Asset(), new Asset()]);

        $mockUser = $this->createMock(User::class);
        $mockUser->expects($this->once())
            ->method('getAssets')
            ->willReturn($mockCollection);

        $this->mockUserRepository->expects($this->once())
            ->method('find')
            ->willReturn($mockUser);

        // Act
        $result = $this->assetService->getAllUserAssets(123);

        // Assert
        $this->assertEquals($mockCollection, $result);
    }
}
