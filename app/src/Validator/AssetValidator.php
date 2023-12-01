<?php

namespace App\Validator;

use App\Entity\Asset;
use App\Exception\InvalidAssetParameterException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AssetValidator
 *
 * Validator for validating Asset objects.
 */
class AssetValidator
{
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * AssetValidator constructor.
     *
     * @param ValidatorInterface $validator Validator interface for validate parameters
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validates an Asset object.
     *
     * @param Asset $asset The Asset object to validate
     *
     * @throws InvalidAssetParameterException When the asset parameters are invalid
     */
    public function validate(Asset $asset): void
    {
        $errors = $this->validator->validate($asset);

        if (count($errors) > 0) {
            $message = $errors->get(0)->getMessage();

            throw new InvalidAssetParameterException($message, Response::HTTP_BAD_REQUEST);
        }
    }
}
