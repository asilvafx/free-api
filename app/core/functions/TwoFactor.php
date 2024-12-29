<?php

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;


class TwoFactor
{
    private $client;

    public function __construct()
    {
        $this->client = new Google2FA();
    }

    function verifyKey($userkey, $twofactorkey, $window = 4)
    {
        return $this->client->verifyKey($userkey, $twofactorkey, $window);
    }
    function generateSecretKey()
    {
        return $this->client->generateSecretKey();
    }
    function getQRCodeUrl($secretKey)
    {
        global $f3;

        try {
        $qrCodeUrl = $this->client->getQRCodeUrl(
            $f3->get('CXT')->displayName,
            $f3->get('CXT')->email,
            $secretKey
        );
        } catch(Exception $e){
          print_r($e);
          return false;
        }

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new ImagickImageBackEnd()
            )
        );

        $qrcode_image = base64_encode($writer->writeString($qrCodeUrl));
        $qrcode_image = 'data:image/png;base64,' . $qrcode_image;
        return $qrcode_image;
       
    }
}
