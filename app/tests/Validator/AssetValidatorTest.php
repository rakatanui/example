<?php

namespace App\Tests\Validator;

use App\Entity\Asset;
use App\Exception\InvalidAssetParameterException;
use App\Validator\AssetValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AssetValidatorTest extends TestCase
{
    private AssetValidator $assetValidator;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetValidator = new AssetValidator($this->validator);
    }

    public function testValidateWithValidAsset(): void
    {
        // Arrange
        $asset = new Asset();
        $asset->setLabel('test asset');
        $asset->setCurrency('BTC');
        $asset->setValue(1);

        $mockConstraintViolationList = $this->createMock(ConstraintViolationListInterface::class);
        $mockConstraintViolationList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($asset)
            ->willReturn($mockConstraintViolationList);

        // Act
        $this->assetValidator->validate($asset);
    }

    public function testValidateWithInvalidAsset(): void
    {
        // Arrange
        $asset = new Asset();

        $mockConstraintViolation = $this->createMock(ConstraintViolation::class);
        $mockConstraintViolation->expects(self::once())
            ->method('getMessage')
            ->willReturn('label cannot be empty.');

        $mockConstraintViolationList = $this->createMock(ConstraintViolationListInterface::class);
        $mockConstraintViolationList->expects(self::once())
            ->method('count')
            ->willReturn(2);
        $mockConstraintViolationList->expects(self::once())
            ->method('get')
            ->with(0)
            ->willReturn($mockConstraintViolation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($asset)
            ->willReturn($mockConstraintViolationList);

        // Assert
        $this->expectException(InvalidAssetParameterException::class);
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        // Act
        $this->assetValidator->validate($asset);
    }
}
