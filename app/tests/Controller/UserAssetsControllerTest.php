<?php

namespace App\Tests\Controller;

use App\Controller\UserAssetsController;
use App\Entity\Asset;
use App\Entity\User;
use App\Exception\InvalidAssetParameterException;
use App\Services\AssetService;
use App\Services\ExchangeRateService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class UserAssetsControllerTest extends WebTestCase
{
    public const USER_ASSETS_URI = 'http://127.0.0.6/api/v1/users/1';

    private UserAssetsController $userAssetsController;
    private AssetService $mockAssetService;
    private KernelBrowser $client;
    private int $assetId;
    private int $userId;
    protected function setUp(): void
    {
        $this->userId = 1;
        $this->assetId = 2;

        $this->mockAssetService = $this->getMockBuilder(AssetService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockSerializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userAssetsController = new UserAssetsController($this->mockAssetService, $mockSerializer);

        $this->client = static::createClient();
        $this->client->getContainer()->set(AssetService::class, $this->mockAssetService);
    }

    public function testCreateAsset()
    {
        // Arrange
        $assetArray = [
            'label' => 'binance',
            'currency' => 'IOTA',
            'value' => 2,
        ];

        $newAsset = new Asset();
        $newAsset->setLabel($assetArray['label']);
        $newAsset->setCurrency($assetArray['currency']);
        $newAsset->setValue($assetArray['value']);

        $this->mockAssetService->expects(self::once())
            ->method('createNewAssetForUser')
            ->with($assetArray, 1)
            ->willReturn($newAsset);

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_POST, self::USER_ASSETS_URI.'/assets', $assetArray);

        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertArrayHasKey('label', $arrayResponse);
        $this->assertArrayHasKey('value', $arrayResponse);
        $this->assertArrayHasKey('currency', $arrayResponse);
    }

    /**
     * @dataProvider createAssetWithInvalidLabelDataProvider
     */
    public function testCreateAssetWithInvalidLabelData(array $assetArray, string $errorMessage)
    {
        // Arrange
        $this->mockAssetService->expects(self::once())
            ->method('createNewAssetForUser')
            ->willThrowException(new InvalidAssetParameterException($errorMessage));

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_POST, self::USER_ASSETS_URI.'/assets', $assetArray);

        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals($errorMessage, $arrayResponse['error']);
    }

    public function createAssetWithInvalidLabelDataProvider(): array
    {
        return [
            [
                'invalid label' => [
                    'label' => '',
                    'currency' => 'BTC',
                    'value' => 0,
                ],
                'label cannot be empty.',
            ],
            [
                'invalid currency' => [
                    'label' => 'binance',
                    'currency' => 'USD',
                    'value' => 0,
                ],
                'currency invalid.',
            ],
            [
                'invalid value' => [
                    'label' => 'binance',
                    'currency' => 'BTC',
                    'value' => -3,
                ],
                'value cannot be negative.',
            ],
        ];
    }

    public function testGetAsset()
    {
        // Arrange
        $asset = new Asset();
        $asset->setId($this->assetId);

        $this->mockAssetService->expects($this->once())
            ->method('getUserAssetById')
            ->with($this->userId, $this->assetId)
            ->willReturn($asset);

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_GET, self::USER_ASSETS_URI . '/assets/' . $this->assetId);

        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($this->assetId, $arrayResponse['id']);
    }

    public function testUpdateAsset()
    {
        // Arrange
        $data = [
            'value' => 101,
        ];

        $asset = new Asset();
        $asset->setId($this->assetId);
        $asset->setValue($data['value']);

        $this->mockAssetService->expects($this->once())
            ->method('updateAsset')
            //->with($data, $this->userId, $this->assetId)
            ->willReturn($asset);

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_PUT, self::USER_ASSETS_URI . '/assets/' . $this->assetId);

        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($this->assetId, $arrayResponse['id']);
        $this->assertEquals($data['value'], $arrayResponse['value']);
    }

    public function testDeleteAsset()
    {
        // Arrange
        $this->mockAssetService->expects($this->once())
            ->method('deleteAsset')
            ->with($this->assetId);

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_DELETE, self::USER_ASSETS_URI . '/assets/' . $this->assetId);

        $response = $this->client->getResponse();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testGetAllUserAssets()
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

        $this->mockAssetService->expects($this->once())
            ->method('getAllUserAssets')
            ->with($this->userId)
            ->willReturn($mockCollection);

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_GET, self::USER_ASSETS_URI . '/assets');

        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($assetArray[0]['label'], $arrayResponse[0]['label']);
        $this->assertEquals($assetArray[0]['value'], $arrayResponse[0]['value']);
        $this->assertEquals($assetArray[0]['currency'], $arrayResponse[0]['currency']);
        $this->assertEquals($assetArray[1]['label'], $arrayResponse[1]['label']);
        $this->assertEquals($assetArray[1]['value'], $arrayResponse[1]['value']);
        $this->assertEquals($assetArray[1]['currency'], $arrayResponse[1]['currency']);

        $this->assertArrayHasKey('label', $arrayResponse[0]);
        $this->assertArrayHasKey('value', $arrayResponse[0]);
        $this->assertArrayHasKey('currency', $arrayResponse[0]);
        $this->assertArrayHasKey('label', $arrayResponse[1]);
        $this->assertArrayHasKey('value', $arrayResponse[1]);
        $this->assertArrayHasKey('currency', $arrayResponse[1]);

        $this->assertCount(2, $arrayResponse);
    }

    public function testDeleteAllUserAssets()
    {
        // Arrange
        $this->mockAssetService->expects($this->once())
            ->method('deleteAllUserAssets')
            ->with($this->userId);

        // Act
        $response = $this->userAssetsController->deleteAllUserAssets($this->userId);

        // Assert
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('{}', $response->getContent());
    }

    public function testGetTotalValueUserAssetsInUSD()
    {
        // Arrange
        $totalValue = 10000;

        $this->mockAssetService->expects($this->once())
            ->method('getTotalValueUserAssetByCurrency')
            ->with($this->userId, ExchangeRateService::USD_CURRENCY)
            ->willReturn($totalValue);

        // Act
        $response = $this->userAssetsController->getTotalValueUserAssetsInUSD($this->userId);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(json_encode($totalValue), $response->getContent());
    }

    public function testGetSeparateValueUserAssetsInUSD()
    {
        // Arrange
        $separateValues = [
            'Bitcoin' => 10000,
            'Ethereum' => 20000,
        ];

        $this->mockAssetService->expects($this->once())
            ->method('getUserAssetsValueByCurrency')
            ->with($this->userId, ExchangeRateService::USD_CURRENCY)
            ->will($this->returnValue($separateValues));

        // Act
        $response = $this->userAssetsController->getSeparateValueUserAssetsInUSD($this->userId);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(json_encode($separateValues), $response->getContent());
    }

    public function testGetAssetWithInvalidAssetId()
    {
        // Arrange
        $assetId = 1000;

        $this->mockAssetService->expects(self::once())
            ->method('getUserAssetById')
            ->willReturn(null);

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_GET, self::USER_ASSETS_URI . '/assets/' . $assetId);

        $response = $this->client->getResponse();

        // Assert
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('{}', $response->getContent());
    }

    public function testDeleteAssetWithInvalidAssetId()
    {
        // Arrange
        $assetId = 1000;

        $this->mockAssetService->expects(self::once())
            ->method('deleteAsset')
            ->willThrowException(new InvalidAssetParameterException('incorrect asset id.', Response::HTTP_NOT_FOUND));

        // Act
        $this->client->loginUser(new User());
        $this->client->request(Request::METHOD_DELETE, self::USER_ASSETS_URI . '/assets/' . $assetId);

        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        // Assert
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('incorrect asset id.', $arrayResponse['error']);
    }

    public function testGetSeparateValueUserAssetsInUSDWithEmptyAssets()
    {
        // Arrange
        $assets = [];

        $this->mockAssetService->expects($this->once())
            ->method('getUserAssetsValueByCurrency')
            ->willReturn($assets);

        // Act
        $response = $this->userAssetsController->getSeparateValueUserAssetsInUSD($this->userId);

        // Assert
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(json_encode($assets), $response->getContent());
    }
}
